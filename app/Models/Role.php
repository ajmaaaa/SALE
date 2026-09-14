<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    const ADMIN = 'admin';

    const ADMIN_PRODI = 'admin_prodi';

    const KAPRODI = 'kaprodi';

    const DOSEN = 'dosen';

    const MAHASISWA = 'mahasiswa';

    protected $fillable = [
        'name',
        'label',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
