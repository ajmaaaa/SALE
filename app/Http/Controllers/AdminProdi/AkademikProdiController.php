<?php

namespace App\Http\Controllers\AdminProdi;

use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\Semester;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AkademikProdiController extends AdminProdiController
{
    public function matakuliahIndex(Request $request): View
    {
        $prodis = $this->allowedProdis();
        $activeProdi = $this->resolveActiveProdi($request);

        $selectedSemesterPaket = $request->filled('semester_paket') ? $request->integer('semester_paket') : null;

        $mataKuliahs = $activeProdi
            ? $activeProdi->mataKuliahs()
                ->when($selectedSemesterPaket, fn ($q) => $q->where('semester_paket', $selectedSemesterPaket))
                ->with(['cpmks.cpls'])
                ->withCount(['classSections', 'cpmks'])
                ->orderBy('semester_paket')
                ->orderBy('code')
                ->get()
            : collect();

        $cpmks = $activeProdi
            ? Cpmk::where('prodi_id', $activeProdi->id)
                ->with('cpls')
                ->orderBy('code')
                ->get()
            : collect();

        return view('admin-prodi.akademik.matakuliah', compact('prodis', 'activeProdi', 'mataKuliahs', 'selectedSemesterPaket', 'cpmks'));
    }

    public function storeMataKuliah(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
            'name' => trim((string) $request->input('name')),
        ]);

        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:prodis,id'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('mata_kuliahs', 'code')->where('prodi_id', $request->input('prodi_id')),
            ],
            'name' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:8'],
            'semester_paket' => ['nullable', 'integer', 'min:1', 'max:8'],
            'is_lintas_prodi' => ['nullable', 'boolean'],
            'cpmk_ids' => ['nullable', 'array'],
            'cpmk_ids.*' => ['exists:cpmks,id'],
        ], [
            'code.unique' => 'Kode mata kuliah sudah digunakan pada program studi ini.',
            'sks.required' => 'Bobot SKS wajib diisi.',
        ]);

        $this->assertProdiScope($validated['prodi_id']);

        $validated['is_lintas_prodi'] = $request->boolean('is_lintas_prodi');
        $cpmkIds = $request->input('cpmk_ids', []);

        $mataKuliah = MataKuliah::create($validated);
        if (!empty($cpmkIds)) {
            $mataKuliah->cpmks()->sync($cpmkIds);
        }

        return redirect()->route('admin-prodi.akademik.matakuliah', ['prodi_id' => $validated['prodi_id']])
            ->with('notice', "Mata Kuliah {$validated['name']} ({$validated['code']}) berhasil ditambahkan.");
    }

    public function updateMataKuliah(Request $request, MataKuliah $mataKuliah): RedirectResponse
    {
        $this->assertMataKuliahScope($mataKuliah);
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('mata_kuliahs', 'code')->where('prodi_id', $mataKuliah->prodi_id)->ignore($mataKuliah->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:8'],
            'semester_paket' => ['nullable', 'integer', 'min:1', 'max:8'],
            'is_lintas_prodi' => ['nullable', 'boolean'],
            'cpmk_ids' => ['nullable', 'array'],
            'cpmk_ids.*' => ['exists:cpmks,id'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);
        $validated['is_lintas_prodi'] = $request->boolean('is_lintas_prodi');

        $mataKuliah->update($validated);
        $mataKuliah->cpmks()->sync($request->input('cpmk_ids', []));

        return redirect()->route('admin-prodi.akademik.matakuliah', ['prodi_id' => $mataKuliah->prodi_id])
            ->with('notice', "Mata Kuliah {$mataKuliah->name} berhasil diperbarui.");
    }

    public function destroyMataKuliah(MataKuliah $mataKuliah): RedirectResponse
    {
        $this->assertMataKuliahScope($mataKuliah);
        $prodiId = $mataKuliah->prodi_id;
        $name = $mataKuliah->name;

        if ($mataKuliah->classSections()->exists()) {
            return back()->withErrors([
                'mata_kuliah' => "Mata kuliah {$name} tidak dapat dihapus karena sudah memiliki kelas perkuliahan.",
            ]);
        }

        $mataKuliah->cpmks()->detach();
        $mataKuliah->delete();

        return redirect()->route('admin-prodi.akademik.matakuliah', ['prodi_id' => $prodiId])
            ->with('notice', "Mata Kuliah {$name} berhasil dihapus.");
    }

    public function kelasIndex(Request $request): View
    {
        $prodis = $this->allowedProdis();
        $activeProdi = $this->resolveActiveProdi($request);

        $semesters = Semester::orderByDesc('id')->get();
        $selectedSemesterId = $request->integer('semester_id') ?: ($semesters->firstWhere('is_active', true)?->id ?? $semesters->first()?->id ?? 0);

        $hasCrossProdiCourse = $activeProdi?->mataKuliahs()->where('is_lintas_prodi', true)->exists() ?? false;
        $dosens = User::withRoleName(Role::DOSEN)
            ->where('is_active', true)
            ->when($activeProdi && ! $hasCrossProdiCourse, fn ($query) => $query->where(function ($subQuery) use ($activeProdi) {
                $subQuery->where('prodi_id', $activeProdi->id)
                    ->orWhere('managing_prodi_id', $activeProdi->id)
                    ->orWhereNull('prodi_id');
            }))
            ->orderBy('name')
            ->get();

        $mataKuliahs = $activeProdi
            ? $activeProdi->mataKuliahs()->orderBy('code')->get()
            : collect();

        $classes = ClassSection::query()
            ->when($activeProdi, fn ($q) => $q->whereHas('mataKuliah', fn ($mk) => $mk->where('prodi_id', $activeProdi->id)))
            ->when($selectedSemesterId, fn ($q) => $q->where('semester_id', $selectedSemesterId))
            ->with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping', 'dosenAnggota'])
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
        $request->merge(['section_code' => strtoupper(trim((string) $request->input('section_code')))]);
        $mataKuliah = MataKuliah::findOrFail($request->integer('mata_kuliah_id'));

        // Pastikan mata kuliah milik prodi yang boleh dikelola admin ini
        $this->assertMataKuliahScope($mataKuliah);

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
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $user = User::with('role')->find($value);
                    if ($user && ! in_array($user->role?->name, [Role::DOSEN], true)) {
                        $fail('Pengguna yang dipilih sebagai dosen pengampu harus memiliki peran Dosen.');
                    }
                },
            ],
            'dosen_pendamping_id' => [
                'nullable',
                'exists:users,id',
                'different:dosen_id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $user = User::with('role')->find($value);
                    if ($user && ! in_array($user->role?->name, [Role::DOSEN], true)) {
                        $fail('Pengguna yang dipilih sebagai dosen anggota harus memiliki peran Dosen.');
                    }
                },
            ],
            'dosen_anggota_ids' => ['nullable', 'array'],
            'dosen_anggota_ids.*' => [
                'nullable',
                'integer',
                'exists:users,id',
                'different:dosen_id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $user = User::with('role')->find($value);
                    if ($user && ! in_array($user->role?->name, [Role::DOSEN], true)) {
                        $fail('Pengguna yang dipilih sebagai dosen anggota harus memiliki peran Dosen.');
                    }
                },
            ],
            
        ], [
            'section_code.unique' => 'Kelas dengan kode seksi ini sudah ada untuk mata kuliah dan semester yang dipilih.',
            'dosen_pendamping_id.different' => 'Dosen Anggota tidak boleh sama dengan Dosen Ketua.',
            'dosen_anggota_ids.*.different' => 'Dosen Anggota tidak boleh sama dengan Dosen Ketua.',
        ]);

        $validated['dosen_id'] = ! empty($validated['dosen_id']) ? (int) $validated['dosen_id'] : null;

        $dosenAnggotaIds = [];
        if ($request->has('dosen_anggota_present') || $request->has('dosen_anggota_ids')) {
            $rawIds = (array) $request->input('dosen_anggota_ids', []);
            $dosenAnggotaIds = array_values(array_unique(array_filter(array_map('intval', $rawIds))));
        } elseif (! empty($validated['dosen_pendamping_id'])) {
            $dosenAnggotaIds = [(int) $validated['dosen_pendamping_id']];
        }

        unset($validated['dosen_anggota_ids'], $validated['dosen_anggota_present']);
        $validated['dosen_pendamping_id'] = $dosenAnggotaIds[0] ?? null;

        $validated['enrollment_code'] = ClassSection::generateUniqueEnrollmentCode();
        $this->validateLecturerProdi($mataKuliah, $validated['dosen_id'] ?? null, 'dosen_id');

        $fieldForAnggota = $request->has('dosen_anggota_ids') ? 'dosen_anggota_ids' : 'dosen_pendamping_id';
        foreach ($dosenAnggotaIds as $anggotaId) {
            $this->validateLecturerProdi($mataKuliah, $anggotaId, $fieldForAnggota);
        }

        $section = ClassSection::create($validated);
        $section->dosenAnggota()->sync($dosenAnggotaIds);
        $mk = $section->mataKuliah;

        $allLecturers = array_values(array_unique(array_filter(array_merge(
            [$section->dosen_id, $section->dosen_pendamping_id],
            $dosenAnggotaIds
        ))));

        if (! empty($allLecturers)) {
            $room = Room::forCourse($section->id, $mk->name);
            foreach ($allLecturers as $lecturerId) {
                RoomMember::firstOrCreate(
                    ['room_id' => $room->id, 'user_id' => $lecturerId],
                    ['role' => 'dosen', 'joined_at' => now()]
                );
            }
        }

        return redirect()->route('admin-prodi.akademik.kelas', [
            'prodi_id' => $mk->prodi_id,
            'semester_id' => $section->semester_id,
        ])->with('notice', "Kelas {$mk->name} - Seksi {$section->section_code} berhasil dibuat. Kode Masuk: {$section->enrollment_code}");
    }

    public function updateKelas(Request $request, ClassSection $section): RedirectResponse
    {
        $this->assertSectionScope($section);
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
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $user = User::with('role')->find($value);
                    if ($user && ! in_array($user->role?->name, [Role::DOSEN], true)) {
                        $fail('Pengguna yang dipilih sebagai dosen pengampu harus memiliki peran Dosen.');
                    }
                },
            ],
            'dosen_pendamping_id' => [
                'nullable',
                'exists:users,id',
                'different:dosen_id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $user = User::with('role')->find($value);
                    if ($user && ! in_array($user->role?->name, [Role::DOSEN], true)) {
                        $fail('Pengguna yang dipilih sebagai dosen anggota harus memiliki peran Dosen.');
                    }
                },
            ],
            'dosen_anggota_ids' => ['nullable', 'array'],
            'dosen_anggota_ids.*' => [
                'nullable',
                'integer',
                'exists:users,id',
                'different:dosen_id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $user = User::with('role')->find($value);
                    if ($user && ! in_array($user->role?->name, [Role::DOSEN], true)) {
                        $fail('Pengguna yang dipilih sebagai dosen anggota harus memiliki peran Dosen.');
                    }
                },
            ],
        ], [
            'section_code.unique' => 'Kode kelas ini sudah ada.',
            'dosen_pendamping_id.different' => 'Dosen Anggota tidak boleh sama dengan Dosen Ketua.',
            'dosen_anggota_ids.*.different' => 'Dosen Anggota tidak boleh sama dengan Dosen Ketua.',
        ]);

        $validated['dosen_id'] = ! empty($validated['dosen_id']) ? (int) $validated['dosen_id'] : null;

        $dosenAnggotaIds = [];
        if ($request->has('dosen_anggota_present') || $request->has('dosen_anggota_ids')) {
            $rawIds = (array) $request->input('dosen_anggota_ids', []);
            $dosenAnggotaIds = array_values(array_unique(array_filter(array_map('intval', $rawIds))));
        } elseif (! empty($validated['dosen_pendamping_id'])) {
            $dosenAnggotaIds = [(int) $validated['dosen_pendamping_id']];
        }

        unset($validated['dosen_anggota_ids'], $validated['dosen_anggota_present']);
        $validated['dosen_pendamping_id'] = $dosenAnggotaIds[0] ?? null;

        $validated['section_code'] = strtoupper(trim($validated['section_code']));
        $this->validateLecturerProdi($section->mataKuliah, $validated['dosen_id'] ?? null, 'dosen_id');

        $fieldForAnggota = $request->has('dosen_anggota_ids') ? 'dosen_anggota_ids' : 'dosen_pendamping_id';
        foreach ($dosenAnggotaIds as $anggotaId) {
            $this->validateLecturerProdi($section->mataKuliah, $anggotaId, $fieldForAnggota);
        }

        $section->update($validated);
        $section->dosenAnggota()->sync($dosenAnggotaIds);

        $allLecturers = array_values(array_unique(array_filter(array_merge(
            [$section->dosen_id, $section->dosen_pendamping_id],
            $dosenAnggotaIds
        ))));

        if (! empty($allLecturers)) {
            $room = Room::forCourse($section->id, $section->mataKuliah->name);
            foreach ($allLecturers as $lecturerId) {
                RoomMember::firstOrCreate(
                    ['room_id' => $room->id, 'user_id' => $lecturerId],
                    ['role' => 'dosen', 'joined_at' => now()]
                );
            }
        }

        return redirect()->route('admin-prodi.akademik.kelas', [
            'prodi_id' => $section->mataKuliah->prodi_id,
            'semester_id' => $section->semester_id,
        ])->with('notice', "Data kelas {$section->display_code} berhasil diperbarui.");
    }

    public function destroyKelas(ClassSection $section): RedirectResponse
    {
        $this->assertSectionScope($section);
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

    private function validateLecturerProdi(MataKuliah $mataKuliah, mixed $userId, string $field): void
    {
        $userId = ! empty($userId) ? (int) $userId : null;
        if (! $userId) {
            return;
        }
        $lecturer = User::findOrFail($userId);
        $belongsToManagedProdi = is_null($lecturer->prodi_id)
            || (int) $lecturer->prodi_id === (int) $mataKuliah->prodi_id
            || (int) $lecturer->managing_prodi_id === (int) $mataKuliah->prodi_id;

        if (! $belongsToManagedProdi && ! $mataKuliah->is_lintas_prodi) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'Dosen dari program studi lain hanya dapat dipilih untuk mata kuliah yang ditandai lintas prodi.',
            ]);
        }

        if (is_null($lecturer->prodi_id)) {
            $lecturer->update(['prodi_id' => $mataKuliah->prodi_id]);
        }
    }

    public function regenerateCode(ClassSection $section): RedirectResponse
    {
        $this->assertSectionScope($section);
        $newCode = ClassSection::generateUniqueEnrollmentCode();
        $section->update(['enrollment_code' => $newCode]);

        return back()->with('notice', "Kode Masuk Kelas {$section->display_code} berhasil diperbarui menjadi {$newCode}.");
    }

    public function qrCode(ClassSection $section): Response
    {
        $this->authorizeEnrollmentCode($section);
        $url = $section->enrollment_url;
        $svg = QrCodeService::svg($url, 260);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    public function barcode(ClassSection $section): Response
    {
        $this->authorizeEnrollmentCode($section);
        $code = $section->enrollment_code;
        $svg = QrCodeService::barcodeSvg($code, 280, 80);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    private function authorizeEnrollmentCode(ClassSection $section): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        if ($user->hasRole(Role::ADMIN)) {
            return;
        }

        if ($user->hasRole(Role::ADMIN_PRODI)) {
            $this->assertSectionScope($section);

            return;
        }

        $isTeaching = $user->can('manage', $section);

        abort_unless($isTeaching, 403, 'Anda tidak berhak melihat kode pendaftaran kelas ini.');
    }
}
