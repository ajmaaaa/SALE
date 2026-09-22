<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AkademikProdiController extends Controller
{
    /**
     * ==========================================
     * 1. MANAJEMEN MATA KULIAH
     * ==========================================
     */
    public function matakuliahIndex(Request $request): View
    {
        $prodis = Prodi::orderBy('name')->get();
        $selectedProdiId = $request->integer('prodi_id') ?: ($prodis->first()?->id ?? 0);
        $activeProdi = $prodis->firstWhere('id', $selectedProdiId) ?? $prodis->first();

        $mataKuliahs = $activeProdi
            ? $activeProdi->mataKuliahs()
                ->withCount(['classSections', 'cpmks'])
                ->orderBy('code')
                ->get()
            : collect();

        return view('admin-prodi.akademik.matakuliah', compact('prodis', 'activeProdi', 'mataKuliahs'));
    }

    public function storeMataKuliah(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:prodis,id'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('mata_kuliahs', 'code')->where('prodi_id', $request->input('prodi_id')),
            ],
            'name' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:8'],
        ], [
            'code.unique' => 'Kode mata kuliah sudah digunakan pada program studi ini.',
            'sks.required' => 'Bobot SKS wajib diisi.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        MataKuliah::create($validated);

        return redirect()->route('admin-prodi.akademik.matakuliah', ['prodi_id' => $validated['prodi_id']])
            ->with('notice', "Mata Kuliah {$validated['name']} ({$validated['code']}) berhasil ditambahkan.");
    }

    public function updateMataKuliah(Request $request, MataKuliah $mataKuliah): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('mata_kuliahs', 'code')->where('prodi_id', $mataKuliah->prodi_id)->ignore($mataKuliah->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:8'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $mataKuliah->update($validated);

        return redirect()->route('admin-prodi.akademik.matakuliah', ['prodi_id' => $mataKuliah->prodi_id])
            ->with('notice', "Mata Kuliah {$mataKuliah->name} berhasil diperbarui.");
    }

    public function destroyMataKuliah(MataKuliah $mataKuliah): RedirectResponse
    {
        $prodiId = $mataKuliah->prodi_id;
        $name = $mataKuliah->name;

        if ($mataKuliah->classSections()->exists() || $mataKuliah->cpmks()->exists()) {
            return back()->withErrors([
                'mata_kuliah' => "Mata kuliah {$name} tidak dapat dihapus karena sudah memiliki kelas perkuliahan atau butir CPMK.",
            ]);
        }

        $mataKuliah->delete();

        return redirect()->route('admin-prodi.akademik.matakuliah', ['prodi_id' => $prodiId])
            ->with('notice', "Mata Kuliah {$name} berhasil dihapus.");
    }

    /**
     * ==========================================
     * 2. MANAJEMEN KELAS PERKULIAHAN
     * ==========================================
     */
    public function kelasIndex(Request $request): View
    {
        $prodis = Prodi::orderBy('name')->get();
        $selectedProdiId = $request->integer('prodi_id') ?: ($prodis->first()?->id ?? 0);
        $activeProdi = $prodis->firstWhere('id', $selectedProdiId) ?? $prodis->first();

        $semesters = Semester::orderByDesc('id')->get();
        $selectedSemesterId = $request->integer('semester_id') ?: ($semesters->firstWhere('is_active', true)?->id ?? $semesters->first()?->id ?? 0);

        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $dosens = User::where('role_id', $dosenRoleId)
            ->orderBy('name')
            ->get();

        $mataKuliahs = $activeProdi
            ? $activeProdi->mataKuliahs()->orderBy('code')->get()
            : collect();

        $classes = ClassSection::query()
            ->when($activeProdi, fn ($q) => $q->whereHas('mataKuliah', fn ($mk) => $mk->where('prodi_id', $activeProdi->id)))
            ->when($selectedSemesterId, fn ($q) => $q->where('semester_id', $selectedSemesterId))
            ->with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])
            ->withCount('students')
            ->orderBy('mata_kuliah_id')
            ->orderBy('section_code')
            ->get();

        return view('admin-prodi.akademik.kelas', compact(
            'prodis',
            'activeProdi',
            'semesters',
            'selectedSemesterId',
            'dosens',
            'mataKuliahs',
            'classes'
        ));
    }

    public function storeKelas(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mata_kuliah_id' => ['required', 'exists:mata_kuliahs,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'section_code' => [
                'required', 'string', 'max:10',
                Rule::unique('class_sections')
                    ->where('mata_kuliah_id', $request->input('mata_kuliah_id'))
                    ->where('semester_id', $request->input('semester_id')),
            ],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
            'dosen_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::with('role')->find($value);
                    if ($user && !in_array($user->role?->name, [\App\Models\Role::DOSEN, \App\Models\Role::KAPRODI], true)) {
                        $fail('Pengguna yang dipilih sebagai dosen pengampu harus memiliki peran Dosen.');
                    }
                },
            ],
            'dosen_pendamping_id' => [
                'nullable',
                'exists:users,id',
                'different:dosen_id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $user = \App\Models\User::with('role')->find($value);
                        if ($user && !in_array($user->role?->name, [\App\Models\Role::DOSEN, \App\Models\Role::KAPRODI], true)) {
                            $fail('Pengguna yang dipilih sebagai dosen pendamping harus memiliki peran Dosen.');
                        }
                    }
                },
            ],
        ], [
            'section_code.unique' => 'Kelas dengan kode seksi ini sudah ada untuk mata kuliah dan semester yang dipilih.',
            'dosen_id.required' => 'Dosen Ketua (Koordinator) wajib ditetapkan.',
            'dosen_pendamping_id.different' => 'Dosen Wakil (Pendamping) tidak boleh sama dengan Dosen Ketua.',
        ]);

        $validated['section_code'] = strtoupper(trim($validated['section_code']));
        $validated['enrollment_code'] = ClassSection::generateUniqueEnrollmentCode();

        $section = ClassSection::create($validated);
        $mk = $section->mataKuliah;

        return redirect()->route('admin-prodi.akademik.kelas', [
            'prodi_id' => $mk->prodi_id,
            'semester_id' => $section->semester_id,
        ])->with('notice', "Kelas {$mk->name} - Seksi {$section->section_code} berhasil dibuat dengan Dosen Ketua {$section->dosen->name} dan Kode Masuk: {$section->enrollment_code}");
    }

    public function updateKelas(Request $request, ClassSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'section_code' => [
                'required', 'string', 'max:10',
                Rule::unique('class_sections')
                    ->where('mata_kuliah_id', $section->mata_kuliah_id)
                    ->where('semester_id', $section->semester_id)
                    ->ignore($section->id),
            ],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
            'dosen_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::with('role')->find($value);
                    if ($user && !in_array($user->role?->name, [\App\Models\Role::DOSEN, \App\Models\Role::KAPRODI], true)) {
                        $fail('Pengguna yang dipilih sebagai dosen pengampu harus memiliki peran Dosen.');
                    }
                },
            ],
            'dosen_pendamping_id' => [
                'nullable',
                'exists:users,id',
                'different:dosen_id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $user = \App\Models\User::with('role')->find($value);
                        if ($user && !in_array($user->role?->name, [\App\Models\Role::DOSEN, \App\Models\Role::KAPRODI], true)) {
                            $fail('Pengguna yang dipilih sebagai dosen pendamping harus memiliki peran Dosen.');
                        }
                    }
                },
            ],
        ], [
            'section_code.unique' => 'Kode kelas ini sudah ada.',
            'dosen_pendamping_id.different' => 'Dosen Wakil tidak boleh sama dengan Dosen Ketua.',
        ]);

        $validated['section_code'] = strtoupper(trim($validated['section_code']));
        $section->update($validated);

        return redirect()->route('admin-prodi.akademik.kelas', [
            'prodi_id' => $section->mataKuliah->prodi_id,
            'semester_id' => $section->semester_id,
        ])->with('notice', "Data kelas {$section->display_code} berhasil diperbarui.");
    }

    public function destroyKelas(ClassSection $section): RedirectResponse
    {
        $prodiId = $section->mataKuliah->prodi_id;
        $semesterId = $section->semester_id;
        $name = $section->display_code;

        if ($section->students()->exists()) {
            return back()->withErrors([
                'kelas' => "Kelas {$name} tidak dapat dihapus karena sudah memiliki mahasiswa terdaftar.",
            ]);
        }

        $section->delete();

        return redirect()->route('admin-prodi.akademik.kelas', [
            'prodi_id' => $prodiId,
            'semester_id' => $semesterId,
        ])->with('notice', "Kelas {$name} berhasil dihapus.");
    }

    public function regenerateCode(ClassSection $section): RedirectResponse
    {
        $newCode = ClassSection::generateUniqueEnrollmentCode();
        $section->update(['enrollment_code' => $newCode]);

        return back()->with('notice', "Kode Masuk Kelas {$section->display_code} berhasil diperbarui menjadi {$newCode}.");
    }

    public function qrCode(ClassSection $section): Response
    {
        $url = $section->enrollment_url;
        $svg = QrCodeService::svg($url, 260);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    public function barcode(ClassSection $section): Response
    {
        $code = $section->enrollment_code;
        $svg = QrCodeService::barcodeSvg($code, 280, 80);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
