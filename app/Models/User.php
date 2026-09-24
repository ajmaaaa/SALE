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

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'prodi_id',
        'nim_nidn',
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
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
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
        return $this->role?->name === $roleName;
    }
}
