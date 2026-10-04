<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Message;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class UrgentSecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    private Role $dosenRole;

    private Role $studentRole;

    private Role $adminProdiRole;

    private Role $adminRole;

    private Prodi $prodiA;

    private Prodi $prodiB;

    private Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $this->dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $this->studentRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $this->adminProdiRole = Role::create(['name' => Role::ADMIN_PRODI, 'label' => 'Admin Prodi']);
        $this->adminRole = Role::create(['name' => Role::ADMIN, 'label' => 'Admin']);
        $this->prodiA = Prodi::create(['code' => 'PA', 'name' => 'Prodi A']);
        $this->prodiB = Prodi::create(['code' => 'PB', 'name' => 'Prodi B']);
        $this->semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
    }

    public function test_private_room_channel_uses_class_membership_and_lecturer_assignment(): void
    {
        [$section, $room, $lecturer, $student] = $this->classRoom($this->prodiA, 'PA101');
        $outsider = $this->user($this->dosenRole, 'outside-lecturer@example.test');
        $admin = $this->user($this->adminRole, 'admin@example.test');

        $this->broadcastAuth($lecturer, $room)->assertOk();
        $this->broadcastAuth($student, $room)->assertOk();
        $this->broadcastAuth($outsider, $room)->assertForbidden();
        $this->broadcastAuth($admin, $room)->assertForbidden();

        DB::table('class_section_student')
            ->where('class_section_id', $section->id)
            ->where('mahasiswa_id', $student->id)
            ->update(['status' => 'kicked']);

        $this->broadcastAuth($student, $room)->assertForbidden();
    }

    public function test_admin_prodi_attachment_access_is_scoped_and_numeric_ids_are_not_routes(): void
    {
        Storage::fake('local');
        [$section] = $this->classRoom($this->prodiA, 'PA102');
        $owner = $this->user($this->studentRole, 'owner@example.test');
        $uuid = (string) Str::uuid();
        $path = 'learning-preview/private.txt';
        Storage::disk('local')->put($path, 'private');
        $attachment = Attachment::create([
            'uuid' => $uuid,
            'user_id' => $owner->id,
            'class_section_id' => $section->id,
            'path' => $path,
            'name' => 'private.txt',
            'mime' => 'text/plain',
            'size' => 7,
        ]);

        $ownAdmin = $this->user($this->adminProdiRole, 'admin-a@example.test', $this->prodiA);
        $otherAdmin = $this->user($this->adminProdiRole, 'admin-b@example.test', $this->prodiB);

        $this->actingAs($ownAdmin)->get(route('preview.file', $uuid))->assertOk();
        $this->actingAs($otherAdmin)->get(route('preview.file', $uuid))->assertForbidden();
        $this->actingAs($ownAdmin)->get('/preview/files/'.$attachment->id)->assertNotFound();
    }

    public function test_chat_rejects_cross_room_reply_and_ignores_out_of_room_mentions(): void
    {
        [, $roomA, $lecturerA, $studentA] = $this->classRoom($this->prodiA, 'PA103');
        [, $roomB, $lecturerB] = $this->classRoom($this->prodiB, 'PB103');
        $foreignMessage = Message::create([
            'room_id' => $roomB->id,
            'user_id' => $lecturerB->id,
            'content' => 'Pesan privat kelas B',
        ]);

        $this->actingAs($studentA)->postJson(route('chat.messages.store', $roomA->course_id), [
            'content' => 'Balasan lintas kelas',
            'reply_to_message_id' => $foreignMessage->id,
        ])->assertNotFound();
        $this->assertDatabaseMissing('messages', ['room_id' => $roomA->id, 'content' => 'Balasan lintas kelas']);

        $response = $this->actingAs($studentA)->postJson(route('chat.messages.store', $roomA->course_id), [
            'content' => 'Mention akun luar kelas',
            'mentioned_user_ids' => [$lecturerB->id],
        ])->assertOk();

        $this->assertDatabaseMissing('message_mentions', [
            'message_id' => $response->json('message.id'),
            'mentioned_user_id' => $lecturerB->id,
        ]);
        $this->assertDatabaseMissing('chat_notifications', [
            'message_id' => $response->json('message.id'),
            'user_id' => $lecturerB->id,
        ]);
        $this->assertDatabaseHas('room_members', ['room_id' => $roomA->id, 'user_id' => $lecturerA->id]);
    }

    public function test_course_code_is_unique_per_prodi_instead_of_globally(): void
    {
        MataKuliah::create(['prodi_id' => $this->prodiA->id, 'code' => 'SHARED101', 'name' => 'Course A']);
        MataKuliah::create(['prodi_id' => $this->prodiB->id, 'code' => 'SHARED101', 'name' => 'Course B']);

        $this->assertSame(2, MataKuliah::where('code', 'SHARED101')->count());

        $this->expectException(QueryException::class);
        MataKuliah::create(['prodi_id' => $this->prodiA->id, 'code' => 'SHARED101', 'name' => 'Duplicate A']);
    }

    public function test_ai_api_key_is_encrypted_at_rest_and_decrypted_for_runtime_use(): void
    {
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => 'secret-provider-key']);

        $stored = DB::table('system_settings')->where('key', 'ai_api_key')->value('value');
        $this->assertNotSame('secret-provider-key', $stored);
        $this->assertStringStartsWith('encrypted:v1:', $stored);
        $this->assertSame('secret-provider-key', SystemSetting::valueFor('ai_api_key'));
    }

    public function test_ai_model_lookup_does_not_accept_api_keys_in_a_get_query_string(): void
    {
        $admin = $this->user($this->adminRole, 'settings-admin@example.test');

        $this->actingAs($admin)
            ->get(route('admin.settings.ai-models', ['ai_provider' => 'Google AI', 'ai_api_key' => 'must-not-enter-logs']))
            ->assertStatus(405);

        $this->actingAs($admin)->postJson(route('admin.settings.ai-models'), [
            'ai_provider' => 'Google AI',
            'ai_api_key' => '',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_database_rejects_duplicate_submission_for_one_assessment_and_student(): void
    {
        [$section, , , $student] = $this->classRoom($this->prodiA, 'PA104');
        $assessment = $section->assessments()->create([
            'code' => 'TGS-1',
            'name' => 'Tugas',
            'type' => 'tugas',
            'final_weight' => 10,
        ]);
        Submission::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'mahasiswa_id' => $student->id,
        ]);

        $this->expectException(QueryException::class);
        Submission::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'mahasiswa_id' => $student->id,
        ]);
    }

    private function classRoom(Prodi $prodi, string $code): array
    {
        $lecturer = $this->user($this->dosenRole, strtolower($code).'@lecturer.test', $prodi);
        $student = $this->user($this->studentRole, strtolower($code).'@student.test', $prodi);
        $course = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => $code, 'name' => $code]);
        $section = ClassSection::create([
            'mata_kuliah_id' => $course->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $lecturer->id,
            'section_code' => 'A',
        ]);
        $section->students()->attach($student->id, ['status' => 'enrolled']);
        $room = Room::create([
            'name' => 'Room '.$code,
            'course_id' => $section->id,
            'class_section_id' => $section->id,
        ]);
        RoomMember::create(['room_id' => $room->id, 'user_id' => $lecturer->id, 'role' => 'dosen', 'joined_at' => now()]);
        RoomMember::create(['room_id' => $room->id, 'user_id' => $student->id, 'role' => 'mahasiswa', 'joined_at' => now()]);

        return [$section, $room, $lecturer, $student];
    }

    private function user(Role $role, string $email, ?Prodi $prodi = null): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => 'password',
            'role_id' => $role->id,
            'prodi_id' => $prodi?->id,
            'managing_prodi_id' => $role->name === Role::ADMIN_PRODI ? $prodi?->id : null,
            'is_active' => true,
        ]);
    }

    private function broadcastAuth(User $user, Room $room)
    {
        return $this->actingAs($user)->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-room.'.$room->id,
        ]);
    }
}
