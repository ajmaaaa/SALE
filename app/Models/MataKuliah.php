<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    protected $fillable = [
        'prodi_id',
        'code',
        'name',
        'sks',
    ];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }

    public function cpmks(): HasMany
    {
        return $this->hasMany(Cpmk::class);
    }
}
