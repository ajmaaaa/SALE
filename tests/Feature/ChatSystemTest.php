<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\Semester;
use App\Models\User;
use App\Support\LearningPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $dosen;
    protected User $mahasiswa1;
    protected User $mahasiswa2;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $this->dosen = User::create([
            'name' => 'Budi Santoso, M.Kom.',
            'email' => 'budi@test.local',
            'password' => 'secret',
            'role_id' => $dosenRole->id,
        ]);

        $this->mahasiswa1 = User::create([
            'name' => 'Ahmad Maulana',
            'email' => 'ahmad@test.local',
            'password' => 'secret',
            'role_id' => $mahasiswaRole->id,
        ]);

        $this->mahasiswa2 = User::create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@test.local',
            'password' => 'secret',
            'role_id' => $mahasiswaRole->id,
        ]);

        $this->room = Room::forCourse(1, 'Struktur Data dan Algoritma');

        RoomMember::create([
            'room_id' => $this->room->id,
            'user_id' => $this->dosen->id,
            'role' => 'dosen',
            'joined_at' => now(),
        ]);

        RoomMember::create([
            'room_id' => $this->room->id,
            'user_id' => $this->mahasiswa1->id,
            'role' => 'mahasiswa',
            'joined_at' => now(),
        ]);

        RoomMember::create([
            'room_id' => $this->room->id,
            'user_id' => $this->mahasiswa2->id,
            'role' => 'mahasiswa',
            'joined_at' => now(),
        ]);
    }

    public function test_can_fetch_messages_with_pinned_and_members(): void
    {
        $response = $this->actingAs($this->mahasiswa1)
            ->getJson(route('chat.messages.index', 1));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'room_id',
                'messages',
                'pinned_messages',
                'members',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_unread_discussions_use_messages_from_the_chat_room(): void
    {
        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027']);
        $mataKuliah = MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'IF204',
            'name' => 'Struktur Data dan Algoritma',
            'sks' => 3,
        ]);
        ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
        ]);

        Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->dosen->id,
            'content' => 'Pesan yang sudah dibaca.',
        ]);
        $unreadMessage = Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->dosen->id,
            'content' => 'Pesan baru dari database chat.',
        ]);

        $this->actingAs($this->mahasiswa1);
        session()->put('learning.discussion_reads.1', 1);

        $unread = LearningPreview::unreadDiscussions(1);

        $this->assertCount(1, $unread);
        $this->assertSame($unreadMessage->content, $unread[0]['message']);
        $this->assertSame($this->dosen->name, $unread[0]['author']);
    }

    public function test_student_and_dosen_can_send_message_with_reply_and_mentions(): void
    {
        // 1. Dosen sends a message
        $dosenResponse = $this->actingAs($this->dosen)
            ->postJson(route('chat.messages.store', 1), [
                'content' => 'Halo mahasiswa, ini pengumuman dari dosen.',
            ]);

        $dosenResponse->assertOk()
            ->assertJson(['success' => true]);

        $dosenMsgId = $dosenResponse->json('message.id');
        $this->assertDatabaseHas('messages', ['id' => $dosenMsgId, 'user_id' => $this->dosen->id]);

        // 2. Mahasiswa replies and mentions the dosen
        $mhsResponse = $this->actingAs($this->mahasiswa1)
            ->postJson(route('chat.messages.store', 1), [
                'content' => "Terima kasih pak @{$this->dosen->name}, siap dimengerti.",
                'reply_to_message_id' => $dosenMsgId,
                'mentioned_user_ids' => [$this->dosen->id],
            ]);

        $mhsResponse->assertOk()
            ->assertJson(['success' => true]);

        $mhsMsgId = $mhsResponse->json('message.id');
        $this->assertDatabaseHas('messages', [
            'id' => $mhsMsgId,
            'user_id' => $this->mahasiswa1->id,
            'reply_to_message_id' => $dosenMsgId,
        ]);
        $this->assertDatabaseHas('message_mentions', [
            'message_id' => $mhsMsgId,
            'mentioned_user_id' => $this->dosen->id,
        ]);
        $this->assertDatabaseHas('chat_notifications', [
            'message_id' => $mhsMsgId,
            'user_id' => $this->dosen->id,
            'type' => 'mention',
        ]);
    }

    public function test_only_dosen_can_pin_message(): void
    {
        $message = Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->dosen->id,
            'content' => 'Pengumuman ujian praktikum.',
        ]);

        // 1. Mahasiswa tries to pin -> 403 Forbidden
        $mhsResponse = $this->actingAs($this->mahasiswa1)
            ->postJson(route('chat.messages.pin', $message->id));
        $mhsResponse->assertForbidden();

        // 2. Dosen pins -> 200 OK
        $dosenResponse = $this->actingAs($this->dosen)
            ->postJson(route('chat.messages.pin', $message->id));
        $dosenResponse->assertOk()
            ->assertJson(['success' => true, 'is_pinned' => true]);

        $this->assertTrue($message->fresh()->is_pinned);
    }

    public function test_message_deletion_authorization(): void
    {
        // Create message by mahasiswa 1
        $mhsMessage = Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->mahasiswa1->id,
            'content' => 'Pesan untuk diuji coba hapus.',
        ]);

        // Mahasiswa 2 cannot delete Mahasiswa 1's message -> 403 Forbidden
        $this->actingAs($this->mahasiswa2)
            ->deleteJson(route('chat.messages.destroy', $mhsMessage->id))
            ->assertForbidden();

        // Dosen CAN delete any student's message (moderation) -> 200 OK
        $this->actingAs($this->dosen)
            ->deleteJson(route('chat.messages.destroy', $mhsMessage->id))
            ->assertOk();

        $this->assertSoftDeleted('messages', ['id' => $mhsMessage->id]);

        // Student can delete their own message
        $ownMessage = Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->mahasiswa2->id,
            'content' => 'Pesan milik sendiri.',
        ]);

        $this->actingAs($this->mahasiswa2)
            ->deleteJson(route('chat.messages.destroy', $ownMessage->id))
            ->assertOk();

        $this->assertSoftDeleted('messages', ['id' => $ownMessage->id]);
    }

    public function test_dosen_accessing_mahasiswa_course_url_is_redirected_seamlessly(): void
    {
        // When Dosen accesses /mahasiswa/course/2
        $response = $this->actingAs($this->dosen)
            ->get('/mahasiswa/course/2');

        $response->assertRedirect(route('dosen.course.show', 2));
    }

    public function test_dosen_can_access_course_item_and_coding_assignment_without_redirect_loop(): void
    {
        // Dosen accessing /dosen/course/1/item/1
        $responseDosenItem = $this->actingAs($this->dosen)
            ->get('/dosen/course/1/item/1');
        $responseDosenItem->assertOk()
            ->assertSee('Pengelolaan Pengampu');

        // Dosen accessing /mahasiswa/course/1/item/1
        $responseMhsItem = $this->actingAs($this->dosen)
            ->get('/mahasiswa/course/1/item/1');
        $responseMhsItem->assertOk();

        // Dosen accessing coding assignment
        $responseCode = $this->actingAs($this->dosen)
            ->get('/mahasiswa/assignment/1/code');
        $responseCode->assertOk();
    }

    public function test_course_view_renders_clean_chat_bubble_without_indentation(): void
    {
        Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->mahasiswa1->id,
            'content' => 'Halo @Budi Santoso, M.Kom. ini pesan uji coba.',
        ]);

        $response = $this->actingAs($this->mahasiswa1)
            ->get(route('mahasiswa.course.show', 1));

        $response->assertOk()->assertSessionHas('learning.discussion_reads.1', 1);
        // Ensure no prose-content in chat bubble
        $response->assertDontSee('<p class="prose-content mt-1.5', false);
    }
}
