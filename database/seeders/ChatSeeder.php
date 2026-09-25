<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\MessageMention;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use App\Support\LearningPreview;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $courses = LearningPreview::courses();
        $dosenUser = User::whereHas('role', fn ($q) => $q->where('name', Role::DOSEN))->first()
            ?? User::where('email', 'budi@example.test')->first();

        $mahasiswaUsers = User::whereHas('role', fn ($q) => $q->where('name', Role::MAHASISWA))->get();
        $ahmad = $mahasiswaUsers->firstWhere('email', 'ahmad.maulana@student.test') ?? $mahasiswaUsers->first();
        $siti = $mahasiswaUsers->firstWhere('email', 'siti.nurhaliza@student.test') ?? $mahasiswaUsers->skip(1)->first();

        foreach ($courses as $courseId => $c) {
            $room = Room::updateOrCreate(
                ['course_id' => $courseId],
                ['name' => "Forum Diskusi - {$c['title']}"]
            );

            // Daftarkan Dosen sebagai anggota room
            if ($dosenUser) {
                RoomMember::updateOrCreate(
                    ['room_id' => $room->id, 'user_id' => $dosenUser->id],
                    ['role' => 'dosen', 'joined_at' => now()]
                );
            }

            // Daftarkan Mahasiswa sebagai anggota room
            foreach ($mahasiswaUsers as $mhs) {
                RoomMember::updateOrCreate(
                    ['room_id' => $room->id, 'user_id' => $mhs->id],
                    ['role' => 'mahasiswa', 'joined_at' => now()]
                );
            }

            // Jika belum ada pesan di room ini, isi pesan awal
            if ($room->messages()->count() === 0) {
                if ($courseId === 1 && $dosenUser && $ahmad) {
                    // Pesan 1: Pengumuman Dosen (di-Pin)
                    $msg1 = Message::create([
                        'room_id' => $room->id,
                        'user_id' => $dosenUser->id,
                        'content' => "Selamat datang di perkuliahan {$c['title']}. Silakan gunakan forum kelas ini untuk tanya jawab seputar materi, tugas, dan kendala praktikum.",
                        'is_pinned' => true,
                        'created_at' => now()->subHours(5),
                    ]);

                    // Pesan 2: Pertanyaan Mahasiswa
                    $msg2 = Message::create([
                        'room_id' => $room->id,
                        'user_id' => $ahmad->id,
                        'content' => "Pak @{$dosenUser->name}, untuk praktikum Binary Tree apakah implementasi delete node juga akan diuji pada kuis akhir nanti?",
                        'is_pinned' => false,
                        'created_at' => now()->subHours(3),
                    ]);

                    // Mention Dosen pada pesan 2
                    MessageMention::create([
                        'message_id' => $msg2->id,
                        'mentioned_user_id' => $dosenUser->id,
                    ]);

                    // Pesan 3: Balasan (Reply) Dosen ke Pertanyaan Mahasiswa
                    $msg3 = Message::create([
                        'room_id' => $room->id,
                        'user_id' => $dosenUser->id,
                        'reply_to_message_id' => $msg2->id,
                        'content' => "Untuk evaluasi modul ini fokus utama pada operasi dasar insertion dan traversal terlebih dahulu ya.",
                        'is_pinned' => false,
                        'created_at' => now()->subHours(2),
                    ]);
                } elseif ($courseId === 2 && $dosenUser && $ahmad) {
                    // Course 2 (IMK)
                    $msg1 = Message::create([
                        'room_id' => $room->id,
                        'user_id' => $dosenUser->id,
                        'content' => "Forum diskusi kelas {$c['title']} telah dibuka. Anda dapat berdiskusi mengenai prinsip evaluasi usability dan desain antarmuka di sini.",
                        'is_pinned' => true,
                        'created_at' => now()->subHours(6),
                    ]);

                    if ($siti) {
                        $msg2 = Message::create([
                            'room_id' => $room->id,
                            'user_id' => $siti->id,
                            'content' => "Pak, untuk laporan usability testing apakah jumlah partisipan minimal 5 orang?",
                            'is_pinned' => false,
                            'created_at' => now()->subHours(4),
                        ]);

                        Message::create([
                            'room_id' => $room->id,
                            'user_id' => $dosenUser->id,
                            'reply_to_message_id' => $msg2->id,
                            'content' => "Benar @{$siti->name}, standar Nielsen Norman Group menyarankan 5 evaluator sudah cukup menemukan 85% masalah usability.",
                            'is_pinned' => false,
                            'created_at' => now()->subHours(3),
                        ]);
                    }
                } else {
                    if ($dosenUser) {
                        Message::create([
                            'room_id' => $room->id,
                            'user_id' => $dosenUser->id,
                            'content' => "Selamat belajar di kelas {$c['title']}. Silakan aktif bertanya dan berdiskusi di forum ini.",
                            'is_pinned' => true,
                            'created_at' => now()->subHours(4),
                        ]);
                    }
                }
            }
        }
    }
}
