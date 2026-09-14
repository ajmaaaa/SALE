<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cpl extends Model
{
    protected $fillable = [
        'prodi_id',
        'code',
        'description',
    ];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /**
     * CPMK that build up this CPL, many-to-many, with the CPMK's
     * contribution weight (%) toward this CPL on the pivot.
     */
    public function cpmks(): BelongsToMany
    {
        return $this->belongsToMany(Cpmk::class, 'cpl_cpmk', 'cpl_id', 'cpmk_id')
            ->withPivot('weight')
            ->withTimestamps();
    }
}
