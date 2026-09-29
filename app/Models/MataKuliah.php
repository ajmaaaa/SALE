<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    protected $fillable = [
        'prodi_id',
        'code',
        'name',
        'sks',
        'semester_paket',
        'is_lintas_prodi',
    ];

    protected function casts(): array
    {
        return [
            'semester_paket' => 'integer',
            'is_lintas_prodi' => 'boolean',
        ];
    }

    public function getSemesterPaketLabelAttribute(): ?string
    {
        return $this->semester_paket ? 'Semester ' . $this->semester_paket : null;
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }

    public function cpmks(): BelongsToMany
    {
        return $this->belongsToMany(Cpmk::class, 'cpmk_mata_kuliah', 'mata_kuliah_id', 'cpmk_id')
            ->withTimestamps();
    }
}
