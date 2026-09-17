<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cpmk extends Model
{
    protected $fillable = [
        'mata_kuliah_id',
        'code',
        'description',
        'threshold',
    ];

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
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
