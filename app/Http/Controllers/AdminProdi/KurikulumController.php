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

        $allCpmks = Cpmk::whereIn('mata_kuliah_id', $mataKuliahs->pluck('id'))
            ->with(['mataKuliah', 'cpls'])
            ->orderBy('mata_kuliah_id')
            ->orderBy('code')
            ->get();

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
            'mata_kuliah_id' => ['required', 'exists:mata_kuliahs,id'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('cpmks', 'code')->where('mata_kuliah_id', $request->input('mata_kuliah_id')),
            ],
            'description' => ['required', 'string', 'max:1000'],
            'threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'cpl_ids' => ['nullable', 'array'],
            'cpl_ids.*' => ['exists:cpls,id'],
            'weights' => ['nullable', 'array'],
        ], [
            'code.unique' => 'Kode CPMK sudah digunakan pada mata kuliah ini.',
            'threshold.required' => 'Standar kelulusan minimum (threshold) wajib diisi.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        $mk = MataKuliah::findOrFail($validated['mata_kuliah_id']);
        $this->assertMataKuliahScope($mk);
        $this->assertCplIdsBelongToProdi($request->input('cpl_ids', []), $mk->prodi_id);

        DB::transaction(function () use ($validated, $request) {
            $cpmk = Cpmk::create([
                'mata_kuliah_id' => $validated['mata_kuliah_id'],
                'code' => $validated['code'],
                'description' => $validated['description'],
                'threshold' => $validated['threshold'],
            ]);

            $cplIds = $request->input('cpl_ids', []);
            $weights = $request->input('weights', []);
            $syncData = [];
            foreach ($cplIds as $cplId) {
                $w = isset($weights[$cplId]) && is_numeric($weights[$cplId]) ? (float) $weights[$cplId] : 100.0;
                $syncData[$cplId] = ['weight' => $w];
            }
            $cpmk->cpls()->sync($syncData);
        });

        return redirect()->route('admin-prodi.kurikulum.index', ['prodi_id' => $mk->prodi_id, 'tab' => 'cpmk'])
            ->with('notice', "CPMK {$validated['code']} untuk mata kuliah {$mk->name} berhasil ditetapkan. Dosen kini dapat memilih CPMK ini.");
    }

    public function updateCpmk(Request $request, Cpmk $cpmk): RedirectResponse
    {
        $this->assertCpmkScope($cpmk);
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('cpmks', 'code')->where('mata_kuliah_id', $cpmk->mata_kuliah_id)->ignore($cpmk->id),
            ],
            'description' => ['required', 'string', 'max:1000'],
            'threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'cpl_ids' => ['nullable', 'array'],
            'cpl_ids.*' => ['exists:cpls,id'],
            'weights' => ['nullable', 'array'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $this->assertCplIdsBelongToProdi($request->input('cpl_ids', []), $cpmk->mataKuliah->prodi_id);

        DB::transaction(function () use ($cpmk, $validated, $request) {
            $cpmk->update([
                'code' => $validated['code'],
                'description' => $validated['description'],
                'threshold' => $validated['threshold'],
            ]);

            $cplIds = $request->input('cpl_ids', []);
            $weights = $request->input('weights', []);
            $syncData = [];
            foreach ($cplIds as $cplId) {
                $w = isset($weights[$cplId]) && is_numeric($weights[$cplId]) ? (float) $weights[$cplId] : 100.0;
                $syncData[$cplId] = ['weight' => $w];
            }
            $cpmk->cpls()->sync($syncData);
        });

        $prodiId = $cpmk->mataKuliah->prodi_id;

        return redirect()->route('admin-prodi.kurikulum.index', ['prodi_id' => $prodiId, 'tab' => 'cpmk'])
            ->with('notice', "CPMK {$cpmk->code} berhasil diperbarui.");
    }

    public function destroyCpmk(Cpmk $cpmk): RedirectResponse
    {
        $this->assertCpmkScope($cpmk);
        $prodiId = $cpmk->mataKuliah->prodi_id;
        $code = $cpmk->code;

        if ($cpmk->assessments()->exists()) {
            return back()->withErrors([
                'cpmk' => "CPMK {$code} tidak dapat dihapus karena sudah diukur oleh asesmen yang dibuat oleh dosen.",
            ]);
        }

        $cpmk->cpls()->detach();
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
            $mkIds = MataKuliah::where('prodi_id', $prodiId)->pluck('id');
            $cpmks = Cpmk::whereIn('mata_kuliah_id', $mkIds)->get();

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
