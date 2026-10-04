<?php

namespace App\Http\Controllers\AdminProdi;

use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\StudentAssessmentCpmkScore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KurikulumController extends AdminProdiController
{
    public function index(Request $request): View
    {
        $prodis = $this->allowedProdis();
        $activeProdi = $this->resolveActiveProdi($request);

        $cpls = $activeProdi ? $activeProdi->cpls()->withCount('cpmks')->orderBy('code')->get() : collect();

        $mataKuliahs = $activeProdi
            ? $activeProdi->mataKuliahs()->with(['cpmks.cpls'])->orderBy('code')->get()
            : collect();

        $allCpmks = $activeProdi
            ? Cpmk::where('prodi_id', $activeProdi->id)
                ->with(['cpls', 'mataKuliahs'])
                ->orderBy('code')
                ->get()
            : collect();

        $tab = $request->query('tab', 'cpl');

        return view('admin-prodi.kurikulum.index', compact(
            'prodis',
            'activeProdi',
            'cpls',
            'mataKuliahs',
            'allCpmks',
            'tab'
        ));
    }

    public function storeCpl(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:prodis,id'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('cpls', 'code')->where('prodi_id', $request->input('prodi_id')),
            ],
            'description' => ['required', 'string', 'max:1000'],
        ], [
            'code.unique' => 'Kode CPL sudah terdaftar pada program studi ini.',
            'description.required' => 'Deskripsi Capaian Pembelajaran Lulusan wajib diisi.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $this->assertProdiScope($validated['prodi_id']);
        Cpl::create($validated);

        return redirect()->route('admin-prodi.kurikulum.index', ['prodi_id' => $validated['prodi_id'], 'tab' => 'cpl'])
            ->with('notice', "Butir {$validated['code']} berhasil ditambahkan ke kurikulum prodi.");
    }

    public function updateCpl(Request $request, Cpl $cpl): RedirectResponse
    {
        $this->assertCplScope($cpl);
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('cpls', 'code')->where('prodi_id', $cpl->prodi_id)->ignore($cpl->id),
            ],
            'description' => ['required', 'string', 'max:1000'],
        ], [
            'code.unique' => 'Kode CPL sudah digunakan pada prodi ini.',
            'description.required' => 'Deskripsi CPL wajib diisi.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $cpl->update($validated);

        return redirect()->route('admin-prodi.kurikulum.index', ['prodi_id' => $cpl->prodi_id, 'tab' => 'cpl'])
            ->with('notice', "Butir {$cpl->code} berhasil diperbarui.");
    }

    public function destroyCpl(Cpl $cpl): RedirectResponse
    {
        $this->assertCplScope($cpl);
        $prodiId = $cpl->prodi_id;
        $code = $cpl->code;

        $linkedCpmkIds = $cpl->cpmks()->pluck('cpmks.id');
        $hasActiveScores = StudentAssessmentCpmkScore::whereIn('cpmk_id', $linkedCpmkIds)->exists();
        if ($hasActiveScores) {
            return redirect()->route('admin-prodi.kurikulum.index', ['prodi_id' => $prodiId, 'tab' => 'cpl'])
                ->withErrors([
                    'cpl' => "CPL {$code} tidak dapat dihapus karena CPMK yang terhubung sudah memiliki data penilaian mahasiswa aktif. Hapus data nilai terlebih dahulu.",
                ]);
        }

        $cpl->cpmks()->detach();
        $cpl->delete();

        return redirect()->route('admin-prodi.kurikulum.index', ['prodi_id' => $prodiId, 'tab' => 'cpl'])
            ->with('notice', "Butir {$code} berhasil dihapus dari kurikulum.");
    }

    public function storeCpmk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prodi_id' => ['nullable', 'exists:prodis,id'],
            'cpl_ids' => ['required', 'array', 'min:1'],
            'cpl_ids.*' => ['exists:cpls,id'],
            'code' => ['required', 'string', 'max:20'],
            'description' => ['required', 'string', 'max:1000'],
            'threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'weights' => ['nullable', 'array'],
        ], [
            'cpl_ids.required' => 'Pilih minimal satu CPL yang didukung oleh CPMK.',
            'cpl_ids.min' => 'Pilih minimal satu CPL yang didukung oleh CPMK.',
            'code.required' => 'Kode CPMK wajib diisi.',
            'description.required' => 'Deskripsi CPMK wajib diisi.',
            'threshold.required' => 'Standar kelulusan minimum (threshold) wajib diisi.',
            'threshold.min' => 'Standar kelulusan minimum (threshold) minimal 0%.',
            'threshold.max' => 'Standar kelulusan minimum (threshold) maksimal 100%.',
        ]);

        $prodiId = (int) ($validated['prodi_id'] ?? $this->adminProdiId() ?? 0);
        abort_unless($prodiId > 0, 400, 'Program studi tidak valid.');
        $this->assertProdiScope($prodiId);
        $this->assertCplIdsBelongToProdi($validated['cpl_ids'], $prodiId);

        $code = strtoupper(trim($validated['code']));

        $exists = Cpmk::where('prodi_id', $prodiId)->where('code', $code)->exists();
        if ($exists) {
            return back()->withInput()->withErrors([
                'code' => "Kode CPMK {$code} sudah terdaftar pada program studi ini.",
            ]);
        }

        $cplIds = $validated['cpl_ids'];
        $weights = $request->input('weights', []);
        $syncData = [];
        foreach ($cplIds as $cplId) {
            $w = isset($weights[$cplId]) && is_numeric($weights[$cplId]) ? (float) $weights[$cplId] : 100.0;
            $syncData[(int) $cplId] = ['weight' => $w];
        }

        $cpmk = DB::transaction(function () use ($prodiId, $code, $validated, $syncData) {
            $cpmk = Cpmk::create([
                'prodi_id' => $prodiId,
                'code' => $code,
                'description' => trim($validated['description']),
                'threshold' => (float) $validated['threshold'],
            ]);

            $cpmk->cpls()->sync($syncData);

            return $cpmk;
        });

        return redirect()->route('admin-prodi.kurikulum.index', ['prodi_id' => $prodiId, 'tab' => 'cpmk'])
            ->with('notice', "Butir CPMK {$cpmk->code} berhasil ditambahkan ke kurikulum program studi.");
    }

    public function syncMataKuliahCpmks(Request $request, MataKuliah $mataKuliah): RedirectResponse
    {
        $this->assertMataKuliahScope($mataKuliah);
        $cpmkIds = $request->input('cpmk_ids', []);

        if (! empty($cpmkIds)) {
            $validCount = Cpmk::where('prodi_id', $mataKuliah->prodi_id)->whereIn('id', $cpmkIds)->count();
            abort_unless($validCount === count($cpmkIds), 403, 'CPMK yang dipilih bukan milik program studi ini.');
        }

        $mataKuliah->cpmks()->sync($cpmkIds);

        return redirect()->route('admin-prodi.kurikulum.index', [
            'prodi_id' => $mataKuliah->prodi_id,
            'tab' => 'cpmk',
        ])->with('notice', "CPMK untuk mata kuliah {$mataKuliah->name} berhasil diperbarui.");
    }

    public function updateCpmk(Request $request, Cpmk $cpmk): RedirectResponse
    {
        $this->assertCpmkScope($cpmk);
        $prodiId = $cpmk->prodi_id ?? $cpmk->mataKuliah?->prodi_id;

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('cpmks', 'code')
                    ->where(fn ($q) => $q->where('prodi_id', $prodiId))
                    ->ignore($cpmk->id),
            ],
            'description' => ['required', 'string', 'max:1000'],
            'threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'cpl_ids' => ['nullable', 'array'],
            'cpl_ids.*' => ['exists:cpls,id'],
            'weights' => ['nullable', 'array'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        if ($request->has('cpl_ids')) {
            $this->assertCplIdsBelongToProdi($request->input('cpl_ids', []), $prodiId);
        }

        DB::transaction(function () use ($cpmk, $validated, $request) {
            $cpmk->update([
                'code' => $validated['code'],
                'description' => $validated['description'],
                'threshold' => $validated['threshold'],
            ]);

            if ($request->has('cpl_ids')) {
                $cplIds = $request->input('cpl_ids', []);
                $weights = $request->input('weights', []);
                $syncData = [];
                foreach ($cplIds as $cplId) {
                    $w = isset($weights[$cplId]) && is_numeric($weights[$cplId]) ? (float) $weights[$cplId] : 100.0;
                    $syncData[$cplId] = ['weight' => $w];
                }
                $cpmk->cpls()->sync($syncData);
            }
        });

        return redirect()->route('admin-prodi.kurikulum.index', ['prodi_id' => $prodiId, 'tab' => 'cpmk'])
            ->with('notice', "CPMK {$cpmk->code} berhasil diperbarui.");
    }

    public function destroyCpmk(Cpmk $cpmk): RedirectResponse
    {
        $this->assertCpmkScope($cpmk);
        $prodiId = $cpmk->prodi_id ?? $cpmk->mataKuliah?->prodi_id;
        $code = $cpmk->code;

        $hasScores = StudentAssessmentCpmkScore::where('cpmk_id', $cpmk->id)->exists();
        if ($hasScores) {
            return back()->withErrors([
                'cpmk' => "CPMK {$code} tidak dapat dihapus karena sudah memiliki data penilaian mahasiswa.",
            ]);
        }

        if ($cpmk->assessments()->exists()) {
            return back()->withErrors([
                'cpmk' => "CPMK {$code} tidak dapat dihapus karena sudah diukur oleh asesmen yang dibuat oleh dosen.",
            ]);
        }

        $cpmk->cpls()->detach();
        $cpmk->mataKuliahs()->detach();
        $cpmk->delete();

        return redirect()->route('admin-prodi.kurikulum.index', ['prodi_id' => $prodiId, 'tab' => 'cpmk'])
            ->with('notice', "CPMK {$code} berhasil dihapus.");
    }

    public function updateMapping(Request $request): RedirectResponse
    {
        $request->validate([
            'prodi_id' => ['required', 'exists:prodis,id'],
            'matrix' => ['nullable', 'array'], // matrix[cpmk_id][cpl_id] = weight
        ]);

        $prodiId = $request->integer('prodi_id');
        $this->assertProdiScope($prodiId);
        $matrix = $request->input('matrix', []);
        $cplIds = collect($matrix)
            ->flatMap(fn ($mappings) => array_keys(is_array($mappings) ? $mappings : []))
            ->unique()
            ->values()
            ->all();
        $this->assertCplIdsBelongToProdi($cplIds, $prodiId);

        DB::transaction(function () use ($matrix, $prodiId) {
            $cpmks = Cpmk::where('prodi_id', $prodiId)->get();

            foreach ($cpmks as $cpmk) {
                $cplMappings = $matrix[$cpmk->id] ?? [];
                $syncData = [];

                foreach ($cplMappings as $cplId => $weight) {
                    if ($weight !== null && $weight !== '' && is_numeric($weight) && (float) $weight > 0) {
                        $syncData[(int) $cplId] = ['weight' => (float) $weight];
                    }
                }

                $cpmk->cpls()->sync($syncData);
            }
        });

        return redirect()->route('admin-prodi.kurikulum.index', ['prodi_id' => $prodiId, 'tab' => 'mapping'])
            ->with('notice', 'Matriks pemetaan CPL ke CPMK berhasil disimpan.');
    }

    private function assertCplIdsBelongToProdi(array $cplIds, int $prodiId): void
    {
        $ids = collect($cplIds)->map(fn ($id) => (int) $id)->filter()->unique();
        if ($ids->isEmpty()) {
            return;
        }

        abort_unless(
            Cpl::where('prodi_id', $prodiId)->whereIn('id', $ids)->count() === $ids->count(),
            403,
            'CPL yang dipilih bukan milik program studi ini.'
        );
    }
}
