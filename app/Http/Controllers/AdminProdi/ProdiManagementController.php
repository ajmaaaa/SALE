<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProdiManagementController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin-prodi.dashboard');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'alpha_num', 'unique:prodis,code'],
            'name' => ['required', 'string', 'max:120'],
        ], [
            'code.required' => 'Kode program studi wajib diisi.',
            'code.unique' => 'Kode program studi sudah digunakan.',
            'name.required' => 'Nama program studi wajib diisi.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        Prodi::create($validated);

        return redirect()->route('admin-prodi.dashboard')
            ->with('notice', "Program Studi {$validated['name']} ({$validated['code']}) berhasil ditambahkan.");
    }

    public function update(Request $request, Prodi $prodi): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'alpha_num', Rule::unique('prodis', 'code')->ignore($prodi->id)],
            'name' => ['required', 'string', 'max:120'],
        ], [
            'code.required' => 'Kode program studi wajib diisi.',
            'code.unique' => 'Kode program studi sudah digunakan oleh prodi lain.',
            'name.required' => 'Nama program studi wajib diisi.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $prodi->update($validated);

        return redirect()->route('admin-prodi.dashboard')
            ->with('notice', "Program Studi {$prodi->name} berhasil diperbarui.");
    }

    public function destroy(Prodi $prodi): RedirectResponse
    {
        if ($prodi->mataKuliahs()->exists() || $prodi->cpls()->exists() || $prodi->users()->exists()) {
            return back()->withErrors([
                'prodi' => "Program Studi {$prodi->name} tidak dapat dihapus karena masih memiliki relasi mata kuliah, kurikulum CPL, atau pengguna terdaftar.",
            ]);
        }

        $name = $prodi->name;
        $prodi->delete();

        return redirect()->route('admin-prodi.dashboard')
            ->with('notice', "Program Studi {$name} berhasil dihapus.");
    }
}
