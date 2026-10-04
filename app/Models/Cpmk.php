<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cpmk extends Model
{
    protected $fillable = [
        'prodi_id',
        'mata_kuliah_id',
        'code',
        'description',
        'threshold',
    ];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function mataKuliahs(): BelongsToMany
    {
        return $this->belongsToMany(MataKuliah::class, 'cpmk_mata_kuliah', 'cpmk_id', 'mata_kuliah_id')
            ->withTimestamps();
    }

    public function scopeForMataKuliah($query, int $mataKuliahId)
    {
        return $query->where(function ($q) use ($mataKuliahId) {
            $q->where('cpmks.mata_kuliah_id', $mataKuliahId)
                ->orWhereHas('mataKuliahs', fn ($m) => $m->where('mata_kuliahs.id', $mataKuliahId));
        });
    }

    /**
     * CPL this CPMK contributes to, many-to-many, with this CPMK's
     * contribution weight (%) toward each CPL on the pivot.
     */
    public function cpls(): BelongsToMany
    {
        return $this->belongsToMany(Cpl::class, 'cpl_cpmk', 'cpmk_id', 'cpl_id')
            ->withPivot('weight')
            ->withTimestamps();
    }

    /**
     * Assessments that measure this CPMK, many-to-many, with each
     * assessment's contribution weight (%) toward this CPMK on the pivot.
     */
    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'assessment_cpmk', 'cpmk_id', 'assessment_id')
            ->withPivot('weight')
            ->withTimestamps();
    }
}
