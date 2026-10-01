<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DosenAnggotaManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminProdi;
    private User $dosenKetua;
    private User $dosenAnggota1;
    private User $dosenAnggota2;
    private Prodi $prodi;
    private Semester $semester;
    private MataKuliah $mataKuliah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminProdiRole = Role::where('name', Role::ADMIN_PRODI)->firstOrFail();
        $dosenRole = Role::where('name', Role::DOSEN)->firstOrFail();

        $this->prodi = Prodi::create([
            'code' => 'IF',
            'name' => 'Informatika',
        ]);

        $this->semester = Semester::create([
            'code' => '2026-1',
            'name' => 'Ganjil 2026/2027',
            'is_active' => true,
        ]);

        $this->mataKuliah = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF201',
            'name' => 'Struktur Data & Algoritma',
            'sks' => 3,
        ]);

        $this->adminProdi = User::create([
            'name' => 'Kaprodi IF',
            'email' => 'kaprodi.if@test.com',
            'password' => Hash::make('password'),
            'role_id' => $adminProdiRole->id,
            'prodi_id' => $this->prodi->id,
            'managing_prodi_id' => $this->prodi->id,
            'nim_nidn' => 'AP001',
        ]);

        $this->dosenKetua = User::create([
            'name' => 'Dr. Dosen Ketua',
            'email' => 'ketua@test.com',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => 'DK001',
        ]);

        $this->dosenAnggota1 = User::create([
            'name' => 'Dosen Anggota Satu, M.Kom',
            'email' => 'anggota1@test.com',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => 'DA001',
        ]);

        $this->dosenAnggota2 = User::create([
            'name' => 'Dosen Anggota Dua, M.T',
            'email' => 'anggota2@test.com',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => 'DA002',
        ]);
    }

    public function test_admin_prodi_can_create_class_with_multiple_dosen_anggota(): void
    {
        $this->actingAs($this->adminProdi);

        $response = $this->post(route('admin-prodi.akademik.kelas.store'), [
            'mata_kuliah_id' => $this->mataKuliah->id,
            'semester_id' => $this->semester->id,
            'section_code' => 'A',
            'capacity' => 40,
            'dosen_id' => $this->dosenKetua->id,
            'dosen_anggota_present' => '1',
            'dosen_anggota_ids' => [$this->dosenAnggota1->id, $this->dosenAnggota2->id],
        ]);

        $response->assertRedirect();

        $section = ClassSection::where('mata_kuliah_id', $this->mataKuliah->id)->first();
        $this->assertNotNull($section);
        $this->assertSame($this->dosenKetua->id, $section->dosen_id);
        $this->assertSame($this->dosenAnggota1->id, $section->dosen_pendamping_id);

        // Verifikasi relasi dosenAnggota memiliki kedua dosen
        $this->assertCount(2, $section->dosenAnggota);
        $this->assertTrue($section->dosenAnggota->contains('id', $this->dosenAnggota1->id));
        $this->assertTrue($section->dosenAnggota->contains('id', $this->dosenAnggota2->id));

        // Verifikasi kebijakan (policy) mengizinkan kedua dosen mengelola kelas
        $this->assertTrue($this->dosenAnggota1->can('manage', $section));
        $this->assertTrue($this->dosenAnggota2->can('manage', $section));

        // Verifikasi kedua Dosen Anggota dapat melihat kelas di halaman mereka
        $this->actingAs($this->dosenAnggota1);
        $resp1 = $this->get(route('dosen.penilaian.index'));
        $resp1->assertOk();
        $resp1->assertSee('IF201-A');
        $resp1->assertSee('Dosen Anggota');

        $this->actingAs($this->dosenAnggota2);
        $resp2 = $this->get(route('dosen.penilaian.index'));
        $resp2->assertOk();
        $resp2->assertSee('IF201-A');
        $resp2->assertSee('Dosen Anggota');

        // Verifikasi di halaman Admin Prodi daftar kelas memuat kedua dosen anggota
        $this->actingAs($this->adminProdi);
        $adminView = $this->get(route('admin-prodi.akademik.kelas', [
            'prodi_id' => $this->prodi->id,
            'semester_id' => $this->semester->id,
        ]));
        $adminView->assertOk();
        $adminView->assertSee('Dosen Anggota');
        $adminView->assertSee('Dosen Anggota Satu, M.Kom');
        $adminView->assertSee('Dosen Anggota Dua, M.T');
    }

    public function test_admin_prodi_can_update_dosen_anggota(): void
    {
        $section = ClassSection::create([
            'mata_kuliah_id' => $this->mataKuliah->id,
            'semester_id' => $this->semester->id,
            'section_code' => 'B',
            'capacity' => 30,
            'dosen_id' => $this->dosenKetua->id,
            'dosen_pendamping_id' => $this->dosenAnggota1->id,
        ]);
        $section->dosenAnggota()->sync([$this->dosenAnggota1->id]);

        $this->actingAs($this->adminProdi);

        // Update menjadi hanya dosenAnggota2
        $response = $this->put(route('admin-prodi.akademik.kelas.update', $section->id), [
            'section_code' => 'B',
            'capacity' => 35,
            'dosen_id' => $this->dosenKetua->id,
            'dosen_anggota_present' => '1',
            'dosen_anggota_ids' => [$this->dosenAnggota2->id],
        ]);

        $response->assertRedirect();

        $freshSection = $section->fresh(['dosenAnggota']);
        $this->assertCount(1, $freshSection->dosenAnggota);
        $this->assertTrue($freshSection->dosenAnggota->contains('id', $this->dosenAnggota2->id));
        $this->assertFalse($freshSection->dosenAnggota->contains('id', $this->dosenAnggota1->id));
        $this->assertSame($this->dosenAnggota2->id, $freshSection->dosen_pendamping_id);
    }
}
