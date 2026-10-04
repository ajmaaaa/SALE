<?php

namespace Tests\Feature;

use App\Models\ChatNotification;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Message;
use App\Models\MessageMention;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Room;
use App\Models\Semester;
use App\Models\User;
use App\Services\DatabaseNotificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DiscussionMentionAndUnreadCountTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $mahasiswaAgus;
    private User $mahasiswaBudi;
    private Prodi $prodi;
    private Semester $semester;
    private ClassSection $section;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $dosenRole = Role::where('name', Role::DOSEN)->firstOrFail();
        $mahasiswaRole = Role::where('name', Role::MAHASISWA)->firstOrFail();

        $this->prodi = Prodi::create([
            'code' => 'IF',
            'name' => 'Informatika',
        ]);

        $this->semester = Semester::create([
            'code' => '2026-1',
            'name' => 'Ganjil 2026/2027',
            'is_active' => true,
        ]);

        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF201',
            'name' => 'Pemrograman Berorientasi Objek',
            'sks' => 3,
        ]);

        $this->dosen = User::create([
            'name' => 'Dosen Pengampu',
            'email' => 'dosen@example.test',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'prodi_id' => $this->prodi->id,
        ]);
        $this->dosen->roles()->syncWithoutDetaching([$dosenRole->id]);

        $this->mahasiswaAgus = User::create([
            'name' => 'Agus Mahasiswa',
            'email' => 'agus@example.test',
            'password' => Hash::make('password'),
            'role_id' => $mahasiswaRole->id,
            'prodi_id' => $this->prodi->id,
            'number' => '12345678',
        ]);
        $this->mahasiswaAgus->roles()->syncWithoutDetaching([$mahasiswaRole->id]);

        $this->mahasiswaBudi = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'password' => Hash::make('password'),
            'role_id' => $mahasiswaRole->id,
            'prodi_id' => $this->prodi->id,
            'number' => '87654321',
        ]);
        $this->mahasiswaBudi->roles()->syncWithoutDetaching([$mahasiswaRole->id]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);

        $this->section->students()->attach($this->mahasiswaAgus->id, ['status' => 'enrolled']);
        $this->section->students()->attach($this->mahasiswaBudi->id, ['status' => 'enrolled']);

        $this->room = Room::forCourse($this->section->id, $mk->name);
    }

    private function cleanHtml(string $html): string
    {
        return (string) preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    }

    public function test_multiple_messages_from_budi_counted_correctly_not_just_one(): void
    {
        // Budi sends 4 messages without mention
        for ($i = 1; $i <= 4; $i++) {
            Message::create([
                'room_id' => $this->room->id,
                'user_id' => $this->mahasiswaBudi->id,
                'content' => "Pesan diskusi nomor {$i} dari Budi",
            ]);
        }

        $service = app(DatabaseNotificationService::class);
        $stats = $service->discussionStatsForSection($this->mahasiswaAgus, $this->section->id);

        // Stats should show 4 unread messages, not 1
        $this->assertSame(4, $stats['unread_count']);
        $this->assertSame(0, $stats['mention_count']);
        $this->assertSame('Pesan diskusi nomor 4 dari Budi', $stats['latest_message']->content);

        // Discussion forum view renders 4 belum dibaca and NO @ badge
        $response = $this->actingAs($this->mahasiswaAgus)->get(route('mahasiswa.discussion.index'));
        $response->assertOk()->assertSee('4 belum dibaca');
        $html = $this->cleanHtml($response->getContent());
        $this->assertStringNotContainsString('data-course-mention-pill', $html);
        $this->assertStringNotContainsString('<span class="font-mono font-black">@</span>', $html);

        // Notification list shows latest message preview without labeled pill
        $notifResponse = $this->actingAs($this->mahasiswaAgus)->get(route('mahasiswa.notifications'));
        $notifResponse->assertOk()
            ->assertSee('Diskusi Baru: IF201-A - Pemrograman Berorientasi Objek')
            ->assertSee('Pesan diskusi nomor 4 dari Budi');
        $notifHtml = $this->cleanHtml($notifResponse->getContent());
        $this->assertStringNotContainsString('<span class="font-mono font-black">@</span>', $notifHtml);
    }

    public function test_mention_badge_displays_when_user_is_mentioned(): void
    {
        // Budi sends 2 normal messages and 1 mention message
        Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->mahasiswaBudi->id,
            'content' => 'Halo semuanya',
        ]);

        $mentionMsg = Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->mahasiswaBudi->id,
            'content' => 'Tolong dicek ya @Agus Mahasiswa',
        ]);

        MessageMention::create([
            'message_id' => $mentionMsg->id,
            'mentioned_user_id' => $this->mahasiswaAgus->id,
        ]);
        ChatNotification::create([
            'user_id' => $this->mahasiswaAgus->id,
            'type' => 'mention',
            'message_id' => $mentionMsg->id,
            'is_read' => false,
        ]);

        $service = app(DatabaseNotificationService::class);
        $stats = $service->discussionStatsForSection($this->mahasiswaAgus, $this->section->id);

        $this->assertSame(2, $stats['unread_count']);
        $this->assertSame(1, $stats['mention_count']);

        // Discussion forum view renders 2 belum dibaca and @1 badge
        $response = $this->actingAs($this->mahasiswaAgus)->get(route('mahasiswa.discussion.index'));
        $response->assertOk()->assertSee('2 belum dibaca');
        $html = $this->cleanHtml($response->getContent());
        $this->assertStringContainsString('data-course-mention-pill', $html);
        $this->assertStringContainsString('<span class="font-mono font-black">@</span>1', $html);

        // Notification item renders @1 badge
        $notifResponse = $this->actingAs($this->mahasiswaAgus)->get(route('mahasiswa.notifications'));
        $notifResponse->assertOk()->assertSee('2 pesan');
        $notifHtml = $this->cleanHtml($notifResponse->getContent());
        $this->assertStringContainsString('<span class="font-mono font-black">@</span>1', $notifHtml);
    }

    public function test_mention_badge_is_hidden_when_mention_count_is_zero(): void
    {
        // Budi sends messages without mentioning Agus
        Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->mahasiswaBudi->id,
            'content' => 'Pesan umum tanpa mention siapapun',
        ]);

        $service = app(DatabaseNotificationService::class);
        $stats = $service->discussionStatsForSection($this->mahasiswaAgus, $this->section->id);

        $this->assertSame(1, $stats['unread_count']);
        $this->assertSame(0, $stats['mention_count']);

        $response = $this->actingAs($this->mahasiswaAgus)->get(route('mahasiswa.discussion.index'));
        $response->assertOk()->assertSee('1 belum dibaca');
        $html = $this->cleanHtml($response->getContent());
        $this->assertStringNotContainsString('data-course-mention-pill', $html);
        $this->assertStringNotContainsString('<span class="font-mono font-black">@</span>', $html);

        $notifResponse = $this->actingAs($this->mahasiswaAgus)->get(route('mahasiswa.notifications'));
        $notifResponse->assertOk();
        $notifHtml = $this->cleanHtml($notifResponse->getContent());
        $this->assertStringNotContainsString('<span class="font-mono font-black">@</span>', $notifHtml);
    }

    public function test_reading_discussion_clears_unread_and_mention_badges(): void
    {
        $mentionMsg = Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->mahasiswaBudi->id,
            'content' => 'Halo @Agus Mahasiswa ada materi baru',
        ]);

        MessageMention::create([
            'message_id' => $mentionMsg->id,
            'mentioned_user_id' => $this->mahasiswaAgus->id,
        ]);
        ChatNotification::create([
            'user_id' => $this->mahasiswaAgus->id,
            'type' => 'mention',
            'message_id' => $mentionMsg->id,
            'is_read' => false,
        ]);

        // Agus visits the course show page (which marks discussion as read)
        $courseResponse = $this->actingAs($this->mahasiswaAgus)->get(route('mahasiswa.course.show', $this->section->id));
        $courseResponse->assertOk();

        // Discussion forum view now shows 'Tidak ada pesan baru' and no mention badge
        $response = $this->actingAs($this->mahasiswaAgus)->get(route('mahasiswa.discussion.index'));
        $response->assertOk()->assertSee('Tidak ada pesan baru');
        $html = $this->cleanHtml($response->getContent());
        $this->assertStringNotContainsString('data-course-mention-pill', $html);
        $this->assertStringNotContainsString('<span class="font-mono font-black">@</span>', $html);

        $service = app(DatabaseNotificationService::class);
        $stats = $service->discussionStatsForSection($this->mahasiswaAgus, $this->section->id);
        $this->assertSame(0, $stats['unread_count']);
        $this->assertSame(0, $stats['mention_count']);
        $this->assertTrue($stats['is_read']);
    }

    public function test_chat_messages_endpoint_returns_total_and_course_page_displays_it(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            Message::create([
                'room_id' => $this->room->id,
                'user_id' => $this->mahasiswaBudi->id,
                'content' => "Test chat message {$i}",
            ]);
        }

        // Endpoint GET /chat/course/{id}/messages returns 'total' => 3
        $chatResponse = $this->actingAs($this->mahasiswaAgus)->getJson("/chat/course/{$this->section->id}/messages");
        $chatResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 3);

        // Course page contains initial total message badge
        $courseResponse = $this->actingAs($this->mahasiswaAgus)->get(route('mahasiswa.course.show', $this->section->id));
        $courseResponse->assertOk()
            ->assertSee('3 pesan');
    }

    public function test_chat_highlights_full_name_of_mentioned_member(): void
    {
        Message::create([
            'room_id' => $this->room->id,
            'user_id' => $this->mahasiswaAgus->id,
            'content' => 'Halo @Budi Santoso mohon konfirmasi tugas.',
        ]);

        $courseResponse = $this->actingAs($this->mahasiswaBudi)->get(route('mahasiswa.course.show', $this->section->id));
        $courseResponse->assertOk();

        // Check that full name @Budi Santoso is wrapped in mention span
        $courseResponse->assertSee('<span class="inline-flex items-center px-1 py-0.2 rounded bg-brand/10 text-brand font-semibold text-[11px]">@Budi Santoso</span>', false);
    }
}
