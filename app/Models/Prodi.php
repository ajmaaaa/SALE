<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prodi extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    public function mataKuliahs(): HasMany
    {
        return $this->hasMany(MataKuliah::class);
    }

    public function cpls(): HasMany
    {
        return $this->hasMany(Cpl::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
