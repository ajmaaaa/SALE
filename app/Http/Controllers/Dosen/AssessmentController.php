<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    /**
     * Halaman 5 — Form Assessment (create).
     */
    public function create(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        return view('dosen.penilaian.assessment-form', [
            'section' => $this->withHeaderCounts($section),
            'cpmks' => $this->cpmksFor($section),
            'assessment' => null,
            'selectedCpmk' => old('cpmk', []),
        ]);
    }

    public function store(Request $request, ClassSection $section): RedirectResponse
    {
        $this->authorizeOwnership($section);

        $data = $this->validated($request, $section);

        $cpmkWeights = $this->validatedCpmkWeights($request);
        if ($cpmkWeights instanceof RedirectResponse) {
            return $cpmkWeights;
        }

        DB::transaction(function () use ($data, $cpmkWeights, $section) {
            $assessment = Assessment::create([
                'class_section_id' => $section->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'final_weight' => $data['final_weight'],
                'status' => $data['status'],
            ]);

            $this->syncCpmk($assessment, $cpmkWeights);
        });

        return redirect()->route('dosen.penilaian.asesmen', $section->id)
            ->with('notice', "Asesmen \"{$data['name']}\" berhasil dibuat.");
    }

    /**
     * Halaman 5 — Form Assessment (edit), same view as create.
     */
    public function edit(ClassSection $section, Assessment $assessment): View
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $assessment->load('cpmks');

        return view('dosen.penilaian.assessment-form', [
            'section' => $this->withHeaderCounts($section),
            'cpmks' => $this->cpmksFor($section),
            'assessment' => $assessment,
            'selectedCpmk' => old('cpmk', $assessment->cpmks->mapWithKeys(fn ($c) => [
                $c->id => $c->pivot->weight,
            ])->all()),
        ]);
    }

    public function update(Request $request, ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $data = $this->validated($request, $section, $assessment);

        $cpmkWeights = $this->validatedCpmkWeights($request);
        if ($cpmkWeights instanceof RedirectResponse) {
            return $cpmkWeights;
        }

        DB::transaction(function () use ($data, $cpmkWeights, $assessment) {
            $assessment->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'final_weight' => $data['final_weight'],
                'status' => $data['status'],
            ]);

            $this->syncCpmk($assessment, $cpmkWeights);
        });

        return redirect()->route('dosen.penilaian.asesmen', $section->id)
            ->with('notice', "Asesmen \"{$data['name']}\" berhasil diperbarui.");
    }

    /**
     * Tambah instrumen komponen asesmen secara cepat langsung dari halaman Matriks Penilaian.
     */
    public function quickStore(Request $request, ClassSection $section): RedirectResponse
    {
        $this->authorizeOwnership($section);

        $request->validate([
            'code' => [
                'required', 'string', 'max:30', 'alpha_dash',
                Rule::unique('assessments', 'code')->where('class_section_id', $section->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:30'],
        ]);

        Assessment::create([
            'class_section_id' => $section->id,
            'code' => strtoupper($request->input('code')),
            'name' => $request->input('name'),
            'type' => strtolower($request->input('type')),
            'final_weight' => 0.0,
            'status' => Assessment::STATUS_PUBLISHED,
        ]);

        return redirect()->route('dosen.penilaian.matriks', $section->id)
            ->with('notice', "Komponen asesmen \"{$request->input('name')}\" berhasil ditambahkan ke matriks.");
    }

    /**
     * Deletes an assessment. Confirmation happens client-side
     * (window.confirm, matching the rest of SALE) before this request
     * is even sent — see the button in dosen.penilaian.asesmen.
     *
     * Refuses to delete an assessment that already has student scores,
     * since silently discarding entered grades would violate "jangan
     * menghapus fitur/data yang masih digunakan tanpa alasan jelas".
     * The Dosen must close the assessment instead in that case.
     */
    public function destroy(ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        if ($assessment->studentScores()->whereNotNull('score')->exists()) {
            return back()->withErrors([
                'assessment' => 'Asesmen ini sudah memiliki nilai mahasiswa dan tidak dapat dihapus. Ubah statusnya menjadi "closed" jika ingin menonaktifkannya.',
            ]);
        }

        $name = $assessment->name;
        $assessment->delete();

        return back()->with('notice', "Asesmen \"{$name}\" telah dihapus.");
    }

    /**
     * Validates the assessment form's plain fields only. The CPMK
     * weight-total rule is checked separately by the caller (store/
     * update) so it can return a proper redirect instead of throwing
     * from inside a private helper.
     */
    private function validated(Request $request, ClassSection $section, ?Assessment $ignoring = null): array
    {
        return $request->validate([
            'code' => [
                'required', 'string', 'max:30', 'alpha_dash',
                Rule::unique('assessments', 'code')
                    ->where('class_section_id', $section->id)
                    ->ignore($ignoring?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'final_weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'status' => ['required', Rule::in([Assessment::STATUS_DRAFT, Assessment::STATUS_PUBLISHED, Assessment::STATUS_CLOSED])],
        ], [
            'code.unique' => 'Kode asesmen sudah digunakan di kelas ini. Gunakan kode lain, misalnya TGS-03.',
        ]);
    }

    /**
     * Reads and validates the CPMK contribution weights from the form:
     * only checkboxes actually ticked count, and the total across them
     * must equal exactly 100%, per the requirement's own validation rule
     * for CPMK contribution on an assessment.
     *
     * @return array<int, float>|RedirectResponse Either the validated
     *  [cpmk_id => weight] map, or a redirect back with errors — the
     *  caller must check with is_array()/instanceof before proceeding.
     */
    private function validatedCpmkWeights(Request $request): array|RedirectResponse
    {
        $request->validate([
            'cpmk' => ['array'],
            'cpmk.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $selected = collect($request->input('cpmk', []))
            ->filter(fn ($weight, $cpmkId) => $request->boolean("cpmk_selected.$cpmkId"))
            ->map(fn ($weight) => (float) $weight);

        if ($selected->isEmpty()) {
            return back()->withErrors(['cpmk' => 'Pilih minimal satu CPMK yang diukur oleh asesmen ini.'])->withInput();
        }

        $total = $selected->sum();
        if (abs($total - 100) > 0.01) {
            $totalLabel = rtrim(rtrim(number_format($total, 2), '0'), '.');

            return back()->withErrors(['cpmk' => "Total kontribusi CPMK harus tepat 100% (saat ini {$totalLabel}%)."])->withInput();
        }

        return $selected->all();
    }

    /**
     * Replaces this assessment's CPMK mapping with the given
     * [cpmk_id => weight] set, scoped to CPMK of this section's mata
     * kuliah only (defence in depth against a tampered request trying
     * to attach a CPMK from an unrelated course).
     */
    private function syncCpmk(Assessment $assessment, array $weightsByCpmkId): void
    {
        $validCpmkIds = $this->cpmksFor($assessment->classSection)->pluck('id');

        $sync = collect($weightsByCpmkId)
            ->filter(fn ($weight, $cpmkId) => $validCpmkIds->contains((int) $cpmkId))
            ->mapWithKeys(fn ($weight, $cpmkId) => [(int) $cpmkId => ['weight' => $weight]]);

        $assessment->cpmks()->sync($sync->all());
    }

    private function cpmksFor(ClassSection $section)
    {
        return Cpmk::where('mata_kuliah_id', $section->mata_kuliah_id)->orderBy('code')->get();
    }

    /**
     * CPL relevant to this section — mirrors PenilaianController::cplsFor()
     * so the header counts stay consistent across every OBE page.
     */
    private function cplsFor(ClassSection $section)
    {
        $cpmkIds = $this->cpmksFor($section)->pluck('id');

        return Cpl::whereHas('cpmks', fn ($q) => $q->whereIn('cpmks.id', $cpmkIds))->get();
    }

    private function withHeaderCounts(ClassSection $section): ClassSection
    {
        $section->loadCount('students')->loadCount('assessments')->load(['mataKuliah', 'semester', 'dosen']);
        $section->cpmk_used_count = $this->cpmksFor($section)->count();
        $section->cpl_used_count = $this->cplsFor($section)->count();

        return $section;
    }

    private function authorizeOwnership(ClassSection $section): void
    {
        abort_unless($section->dosen_id === Auth::guard('web')->id(), 403);
    }

    private function authorizeAssessmentBelongsToSection(ClassSection $section, Assessment $assessment): void
    {
        abort_unless($assessment->class_section_id === $section->id, 404);
    }
}
