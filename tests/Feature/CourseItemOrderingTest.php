<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CourseItemOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_materi_and_tugas_are_ordered_from_newest_to_oldest(): void
    {
        $this->seed(RoleSeeder::class);
        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $mhsRoleId = Role::where('name', Role::MAHASISWA)->value('id');

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        $semester = Semester::create(['name' => 'Ganjil 2026/2027', 'code' => '20261', 'is_active' => true]);
        $matkul = MataKuliah::create(['code' => 'IF101', 'name' => 'Algoritma Pemrograman', 'prodi_id' => $prodi->id, 'sks' => 3]);

        $dosen = User::create([
            'name' => 'Dr. Dosen',
            'email' => 'dosen@test.local',
            'nim_nidn' => '12345678',
            'password' => Hash::make('secret'),
            'role_id' => $dosenRoleId,
        ]);
        $dosen->roles()->sync([$dosenRoleId]);

        $student = User::create([
            'name' => 'Mahasiswa Test',
            'email' => 'mhs@test.local',
            'nim_nidn' => '98765432',
            'password' => Hash::make('secret'),
            'role_id' => $mhsRoleId,
        ]);
        $student->roles()->sync([$mhsRoleId]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $matkul->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);
        $section->students()->attach($student->id);

        // Buat Materi 1 (Lama)
        $materi1 = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'MAT-01',
            'name' => 'Materi Pertemuan Pertama',
            'type' => 'materi',
            'description' => 'Materi pengantar',
            'final_weight' => 0,
            'status' => 'published',
        ]);
        $materi1->forceFill([
            'created_at' => Carbon::now()->subDays(5),
            'updated_at' => Carbon::now()->subDays(5),
            'published_at' => Carbon::now()->subDays(5),
        ])->saveQuietly();

        // Buat Materi 2 (Baru)
        $materi2 = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'MAT-02',
            'name' => 'Materi Pertemuan Kedua',
            'type' => 'materi',
            'description' => 'Materi lanjutan',
            'final_weight' => 0,
            'status' => 'published',
        ]);
        $materi2->forceFill([
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
            'published_at' => Carbon::now()->subDays(2),
        ])->saveQuietly();

        // Buat Tugas 1 (Lama, deadline besok)
        $tugas1 = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas Awal Dasar',
            'type' => 'tugas',
            'description' => 'Tugas pertama',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => Carbon::now()->addDays(1),
        ]);
        $tugas1->forceFill([
            'created_at' => Carbon::now()->subDays(6),
            'updated_at' => Carbon::now()->subDays(6),
            'published_at' => Carbon::now()->subDays(6),
        ])->saveQuietly();

        // Buat Tugas 2 (Baru, deadline minggu depan)
        $tugas2 = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-02',
            'name' => 'Tugas Pemrograman Terbaru',
            'type' => 'coding',
            'description' => 'Tugas coding baru',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => Carbon::now()->addDays(7),
        ]);
        $tugas2->forceFill([
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
            'published_at' => Carbon::now()->subDay(),
        ])->saveQuietly();

        // Akses course sebagai dosen
        $response = $this->actingAs($dosen)->get(route('dosen.course.show', $section->id));
        $response->assertOk();

        $content = $response->getContent();

        // Materi terbaru harus muncul sebelum materi lama
        $posMateri2 = strpos($content, 'Materi Pertemuan Kedua');
        $posMateri1 = strpos($content, 'Materi Pertemuan Pertama');
        $this->assertNotFalse($posMateri2);
        $this->assertNotFalse($posMateri1);
        $this->assertTrue($posMateri2 < $posMateri1, 'Materi kedua (terbaru) harus berada di atas materi pertama');

        // Tugas terbaru harus muncul sebelum tugas lama
        $posTugas2 = strpos($content, 'Tugas Pemrograman Terbaru');
        $posTugas1 = strpos($content, 'Tugas Awal Dasar');
        $this->assertNotFalse($posTugas2);
        $this->assertNotFalse($posTugas1);
        $this->assertTrue($posTugas2 < $posTugas1, 'Tugas terbaru harus berada di atas tugas lama');

        // Update Materi 1 sehingga menjadi materi yang paling baru diedit
        $materi1->forceFill([
            'name' => 'Materi Pertemuan Pertama (Revisi Terbaru)',
            'updated_at' => Carbon::now(),
        ])->saveQuietly();

        $responseUpdated = $this->actingAs($dosen)->get(route('dosen.course.show', $section->id));
        $responseUpdated->assertOk();
        $updatedContent = $responseUpdated->getContent();

        $posMateri1Revisi = strpos($updatedContent, 'Materi Pertemuan Pertama (Revisi Terbaru)');
        $posMateri2After = strpos($updatedContent, 'Materi Pertemuan Kedua');
        $this->assertTrue($posMateri1Revisi < $posMateri2After, 'Materi pertama yang baru direvisi harus naik ke paling atas');

        // Akses course sebagai mahasiswa
        $responseMhs = $this->actingAs($student)->get(route('mahasiswa.course.show', $section->id));
        $responseMhs->assertOk();
        $mhsContent = $responseMhs->getContent();

        $mhsPosMateri1 = strpos($mhsContent, 'Materi Pertemuan Pertama (Revisi Terbaru)');
        $mhsPosMateri2 = strpos($mhsContent, 'Materi Pertemuan Kedua');
        $this->assertTrue($mhsPosMateri1 < $mhsPosMateri2, 'Mahasiswa melihat materi terbaru di atas');

        $mhsPosTugas2 = strpos($mhsContent, 'Tugas Pemrograman Terbaru');
        $mhsPosTugas1 = strpos($mhsContent, 'Tugas Awal Dasar');
        $this->assertTrue($mhsPosTugas2 < $mhsPosTugas1, 'Mahasiswa melihat tugas terbaru di atas');
    }
}
