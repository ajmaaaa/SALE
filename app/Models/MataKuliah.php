<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        return $this->semester_paket ? 'Semester '.$this->semester_paket : null;
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
            ->withPivot('cpl_id')
            ->withTimestamps();
    }

    public function cpls(): BelongsToMany
    {
        return $this->belongsToMany(Cpl::class, 'cpmk_mata_kuliah', 'mata_kuliah_id', 'cpl_id')
            ->distinct()
            ->withTimestamps();
    }

    /**
     * Mengembalikan daftar string pasangan "cpmkId_cplId" atau "cpmkId_unmapped"
     * yang tersimpan untuk mata kuliah ini.
     *
     * @return array<string>
     */
    public function getCpmkCplPairsAttribute(): array
    {
        return DB::table('cpmk_mata_kuliah')
            ->where('mata_kuliah_id', $this->id)
            ->get()
            ->map(function ($row) {
                $cplPart = $row->cpl_id !== null ? (string) $row->cpl_id : 'unmapped';

                return "{$row->cpmk_id}_{$cplPart}";
            })
            ->all();
    }

    /**
     * CPL yang secara kontekstual terhubung ke mata kuliah ini melalui cpmk_mata_kuliah,
     * lengkap dengan butir CPMK spesifik yang diampu pada masing-masing CPL.
     */
    public function contextualCpls(): Collection
    {
        $rows = DB::table('cpmk_mata_kuliah')
            ->where('mata_kuliah_id', $this->id)
            ->whereNotNull('cpl_id')
            ->get();

        if ($rows->isEmpty()) {
            // Fallback untuk backward compatibility jika cpl_id belum terisi
            $cpmkIds = Cpmk::forMataKuliah($this->id)->pluck('id');

            return Cpl::whereHas('cpmks', fn ($q) => $q->whereIn('cpmks.id', $cpmkIds))
                ->with(['cpmks' => fn ($q) => $q->whereIn('cpmks.id', $cpmkIds)])
                ->orderBy('code')
                ->get();
        }

        $cplIds = $rows->pluck('cpl_id')->unique()->all();
        $cpls = Cpl::whereIn('id', $cplIds)->orderBy('code')->get();

        $cpmkIds = $rows->pluck('cpmk_id')->unique()->all();
        $cpmks = Cpmk::whereIn('id', $cpmkIds)->orderBy('code')->get()->keyBy('id');

        $cplCpmkWeights = DB::table('cpl_cpmk')
            ->whereIn('cpl_id', $cplIds)
            ->whereIn('cpmk_id', $cpmkIds)
            ->get()
            ->groupBy(fn ($r) => $r->cpl_id.'_'.$r->cpmk_id);

        foreach ($cpls as $cpl) {
            $assignedCpmkIds = $rows->where('cpl_id', $cpl->id)->pluck('cpmk_id')->all();
            $mappedCpmks = collect($assignedCpmkIds)->map(function ($id) use ($cpmks, $cpl, $cplCpmkWeights) {
                $cpmk = $cpmks->get($id);
                if (! $cpmk) {
                    return null;
                }
                $cpmkInstance = clone $cpmk;
                $weight = (float) ($cplCpmkWeights->get("{$cpl->id}_{$cpmk->id}")?->first()?->weight ?? 100.0);
                $cpmkInstance->setRelation('pivot', new Pivot([
                    'cpl_id' => $cpl->id,
                    'cpmk_id' => $cpmk->id,
                    'weight' => $weight,
                ]));

                return $cpmkInstance;
            })->filter()->values();

            $cpl->setRelation('cpmks', $mappedCpmks);
        }

        return $cpls;
    }
}
