<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\User;

/**
 * Base controller untuk semua admin-prodi.
 *
 * Menyediakan helper scope prodi agar setiap controller turunan
 * tidak perlu mengulang logika ownership. Admin global (role admin)
 * adalah satu-satunya pengecualian eksplisit yang boleh mengakses
 * semua prodi.
 */
abstract class AdminProdiController extends Controller
{
    /**
     * Kembalikan prodi_id milik admin yang sedang login.
     * Null berarti admin global (akses semua prodi).
     */
    protected function adminProdiId(): ?int
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        // Admin global tidak dibatasi prodi
        if ($user->hasRole(Role::ADMIN)) {
            return null;
        }

        return $user->prodi_id ?: null;
    }

    /**
     * Pastikan $requestedProdiId boleh diakses oleh admin yang login.
     * Lempar 403 jika admin prodi mencoba akses prodi lain.
     */
    protected function assertProdiScope(int $requestedProdiId): void
    {
        $ownProdiId = $this->adminProdiId();

        // null = admin global, boleh akses semua
        if ($ownProdiId === null) {
            return;
        }

        abort_if(
            $ownProdiId !== $requestedProdiId,
            403,
            'Anda tidak berwenang mengelola data program studi ini.'
        );
    }

    /**
     * Pastikan MataKuliah milik prodi yang boleh dikelola admin ini.
     */
    protected function assertMataKuliahScope(MataKuliah $mataKuliah): void
    {
        $this->assertProdiScope($mataKuliah->prodi_id);
    }

    /**
     * Pastikan ClassSection milik prodi yang boleh dikelola admin ini.
     */
    protected function assertSectionScope(ClassSection $section): void
    {
        $section->loadMissing('mataKuliah');
        $this->assertProdiScope($section->mataKuliah->prodi_id);
    }

    /**
     * Pastikan User (dosen/mahasiswa) milik prodi yang boleh dikelola admin ini.
     */
    protected function assertUserScope(User $user): void
    {
        abort_unless(
            $user->hasRole(Role::DOSEN) || $user->hasRole(Role::MAHASISWA),
            404,
            'Pengguna tidak termasuk lingkup pengelolaan Admin Program Studi.'
        );

        if ($user->prodi_id) {
            $this->assertProdiScope($user->prodi_id);
        }
    }

    /**
     * Pastikan CPL milik prodi yang boleh dikelola admin ini.
     */
    protected function assertCplScope(Cpl $cpl): void
    {
        $this->assertProdiScope($cpl->prodi_id);
    }

    /**
     * Pastikan CPMK milik prodi yang boleh dikelola admin ini.
     */
    protected function assertCpmkScope(Cpmk $cpmk): void
    {
        $cpmk->loadMissing('mataKuliah');
        $this->assertProdiScope($cpmk->mataKuliah->prodi_id);
    }

    /**
     * Kembalikan daftar Prodi yang boleh dilihat/dikelola admin ini.
     * Admin global mendapatkan semua prodi; admin prodi hanya prodinya sendiri.
     */
    protected function allowedProdis()
    {
        $ownProdiId = $this->adminProdiId();

        return $ownProdiId === null
            ? Prodi::orderBy('name')->get()
            : Prodi::where('id', $ownProdiId)->orderBy('name')->get();
    }

    /**
     * Resolve prodi aktif dari request dengan memaksa scope prodi.
     * Jika prodi_id tidak diberikan, gunakan prodi sendiri (untuk admin prodi)
     * atau prodi pertama (untuk admin global).
     */
    protected function resolveActiveProdi(\Illuminate\Http\Request $request): ?Prodi
    {
        $prodis = $this->allowedProdis();
        $ownProdiId = $this->adminProdiId();

        $requestedId = $request->integer('prodi_id') ?: null;

        if ($requestedId) {
            // Pastikan prodi yang diminta boleh diakses
            $this->assertProdiScope($requestedId);
            return $prodis->firstWhere('id', $requestedId) ?? $prodis->first();
        }

        // Default: untuk admin prodi gunakan prodinya, untuk admin global prodi pertama
        return $ownProdiId
            ? $prodis->firstWhere('id', $ownProdiId)
            : $prodis->first();
    }
}
