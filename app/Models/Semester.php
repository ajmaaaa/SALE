<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $fillable = [
        'code',
        'name',
        'academic_year',
        'term',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'term' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getTermLabelAttribute(): string
    {
        return match ($this->term) {
            1 => 'Ganjil',
            2 => 'Genap',
            3 => 'Pendek',
            default => str_contains(strtolower($this->name), 'genap') ? 'Genap' : 'Ganjil',
        };
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->academic_year && $this->term) {
            return $this->term_label . ' ' . $this->academic_year;
        }

        return $this->name;
    }

    public function scopeOrderChronological($query, string $direction = 'desc')
    {
        return $query->orderBy('academic_year', $direction)
            ->orderBy('term', $direction)
            ->orderBy('id', $direction);
    }

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }
}
