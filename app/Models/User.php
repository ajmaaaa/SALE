<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saved(function (self $user): void {
            if ($user->role_id && ! $user->roles()->whereKey($user->role_id)->exists()) {
                $user->roles()->attach($user->role_id);
            }
        });
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'prodi_id',
        'managing_prodi_id',
        'nim_nidn',
        'angkatan',
        'must_change_password',
        'is_active',
        'profile_photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'angkatan' => 'integer',
            'notification_preferences' => 'array',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        if (! app()->runningUnitTests() && ! Storage::disk('public')->exists($this->profile_photo_path)) {
            return null;
        }

        // URL relatif tetap bekerja baik saat SALE dibuka lewat localhost,
        // alamat LAN, reverse proxy, maupun port pengembangan yang berbeda.
        return '/storage/'.ltrim($this->profile_photo_path, '/');
    }

    public function getAngkatanAttribute($value): ?int
    {
        if ($value !== null && (int) $value > 0) {
            return (int) $value;
        }

        if (! empty($this->nim_nidn) && preg_match('/^(\d{2})/', (string) $this->nim_nidn, $matches)) {
            $yearPrefix = (int) $matches[1];

            return $yearPrefix >= 50 ? (1900 + $yearPrefix) : (2000 + $yearPrefix);
        }

        return null;
    }

    public function semesterTempuhAt(?Semester $semester = null): int
    {
        $semester ??= Semester::firstWhere('is_active', true) ?? Semester::latest('id')->first();
        if (! $semester || ! $this->angkatan) {
            return 1;
        }

        $startYear = $semester->academic_year_start ?? 0;
        if ($startYear <= 0 && ! empty($semester->academic_year)) {
            $parts = explode('/', (string) $semester->academic_year);
            $startYear = (int) $parts[0];
        }
        if ($startYear <= 0 && ! empty($semester->name)) {
            if (preg_match('/(\d{4})/', (string) $semester->name, $matches)) {
                $startYear = (int) $matches[1];
            }
        }
        if ($startYear <= 0 && ! empty($semester->code)) {
            $startYear = (int) substr((string) $semester->code, 0, 4);
        }
        if ($startYear <= 0) {
            return 1;
        }

        $yearDiff = $startYear - (int) $this->angkatan;
        $termOffset = ($semester->term === 2 || str_contains(strtolower((string) ($semester->name . ' ' . $semester->code)), 'genap')) ? 2 : 1;

        $calculated = ($yearDiff * 2) + $termOffset;

        return max(1, $calculated);
    }

    public function getSemesterTempuhAttribute(): int
    {
        return $this->semesterTempuhAt();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function managingProdi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'managing_prodi_id');
    }

    public function notificationStates(): HasMany
    {
        return $this->hasMany(UserNotificationState::class);
    }

    public function classSectionsTeaching(): HasMany
    {
        return $this->hasMany(ClassSection::class, 'dosen_id');
    }

    public function classSectionsAssisting(): HasMany
    {
        return $this->hasMany(ClassSection::class, 'dosen_pendamping_id');
    }

    public function classSectionsAnggota(): BelongsToMany
    {
        return $this->belongsToMany(ClassSection::class, 'class_section_dosen_anggota', 'dosen_id', 'class_section_id')
            ->withTimestamps();
    }

    public function totalClassSectionsTeachingCount(): int
    {
        return ClassSection::where(function ($q) {
            $q->where('dosen_id', $this->id)
                ->orWhere('dosen_pendamping_id', $this->id)
                ->orWhereHas('dosenAnggota', fn ($sub) => $sub->where('users.id', $this->id));
        })->count();
    }

    /**
     * Kelas yang sedang diikuti secara aktif (status enrolled).
     */
    public function classSectionsEnrolled(): BelongsToMany
    {
        return $this->belongsToMany(ClassSection::class, 'class_section_student', 'mahasiswa_id', 'class_section_id')
            ->withPivotValue('status', 'enrolled')
            ->withTimestamps();
    }

    /**
     * Seluruh riwayat keanggotaan kelas (aktif, keluar sendiri, dikeluarkan).
     */
    public function classSectionEnrollmentRecords(): BelongsToMany
    {
        return $this->belongsToMany(ClassSection::class, 'class_section_student', 'mahasiswa_id', 'class_section_id')
            ->withPivot(['status', 'kick_count', 'kicked_at', 'kick_reason', 'dropped_at', 'is_locked'])
            ->withTimestamps();
    }

    public function hasRole(string $roleName): bool
    {
        if ($this->role?->name === $roleName) {
            return true;
        }

        return $this->relationLoaded('roles')
            ? $this->roles->contains('name', $roleName)
            : $this->roles()->where('name', $roleName)->exists();
    }

    public function scopeWithRoleName($query, string $roleName)
    {
        return $query->where(function ($builder) use ($roleName) {
            $builder->whereHas('role', fn ($roleQuery) => $roleQuery->where('name', $roleName))
                ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $roleName));
        });
    }
}
