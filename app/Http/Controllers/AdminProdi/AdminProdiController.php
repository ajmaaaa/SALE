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
use Illuminate\Http\Request;

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
     * Kembalikan prodi_id milik admin prodi yang sedang login.
     */
    protected function adminProdiId(): int
    {
        $user = auth()->user();
        abort_unless($user, 403);

        abort_unless(
            $user->hasRole(Role::ADMIN_PRODI),
            403,
            'Akses ditolak. Halaman ini khusus Admin Program Studi.'
        );

        $managedProdiId = $user->managing_prodi_id ?? $user->prodi_id;
        if (! $managedProdiId) {
            abort(403, 'Akun Admin Prodi belum ditugaskan ke Program Studi manapun. Silakan hubungi Administrator Sistem.');
        }

        return (int) $managedProdiId;
    }

    protected function assertGlobalAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::ADMIN), 403, 'Tindakan ini hanya dapat dilakukan Admin global.');
    }

    /**
     * Pastikan $requestedProdiId boleh diakses oleh admin yang login.
     * Lempar 403 jika admin prodi mencoba akses prodi lain.
     */
    protected function assertProdiScope(int $requestedProdiId): void
    {
        $ownProdiId = $this->adminProdiId();

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

        $scopeProdiId = $user->prodi_id ?? $user->managing_prodi_id;
        abort_unless($scopeProdiId, 404, 'Pengguna belum terhubung ke program studi pengelola.');
        $this->assertProdiScope((int) $scopeProdiId);
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
        $prodiId = $cpmk->prodi_id ?? $cpmk->mataKuliah?->prodi_id;
        abort_unless($prodiId, 404, 'CPMK tidak terhubung ke program studi.');
        $this->assertProdiScope((int) $prodiId);
    }

    /**
     * Kembalikan daftar Prodi yang boleh dilihat/dikelola admin prodi ini.
     */
    protected function allowedProdis()
    {
        $ownProdiId = $this->adminProdiId();

        return Prodi::where('id', $ownProdiId)->orderBy('name')->get();
    }

    /**
     * Resolve prodi aktif dari request dengan memaksa scope prodi.
     */
    protected function resolveActiveProdi(Request $request, bool $allowDefaultFirst = false): ?Prodi
    {
        $prodis = $this->allowedProdis();
        $ownProdiId = $this->adminProdiId();

        $requestedId = $request->integer('prodi_id') ?: null;

        if ($requestedId) {
            $this->assertProdiScope($requestedId);

            return $prodis->firstWhere('id', $requestedId) ?? Prodi::find($requestedId);
        }

        return $prodis->firstWhere('id', $ownProdiId) ?? Prodi::find($ownProdiId);
    }
}
