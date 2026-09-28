<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'must_change_password',
        'is_active',
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

        // URL relatif tetap bekerja baik saat SALE dibuka lewat localhost,
        // alamat LAN, reverse proxy, maupun port pengembangan yang berbeda.
        return '/storage/'.ltrim($this->profile_photo_path, '/');
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

    public function classSectionsEnrolled(): BelongsToMany
    {
        return $this->belongsToMany(ClassSection::class, 'class_section_student', 'mahasiswa_id', 'class_section_id')
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
