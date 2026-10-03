<?php

namespace App\Services\Ai;

enum AiErrorCode: string
{
    case ProviderBusy = 'provider_busy';
    case QuotaDaily = 'quota_daily';
    case TurnLimit = 'turn_limit';
    case BlockedAsksSolution = 'blocked_asks_solution';
    case BlockedOffTopic = 'blocked_off_topic';
    case Locked = 'locked';
    case InternalError = 'internal_error';

    public function message(): string
    {
        return match ($this) {
            self::ProviderBusy => 'AI sedang sibuk. Coba lagi dalam beberapa detik.',
            self::QuotaDaily => 'Kuota AI harianmu habis. Reset pukul 07.00 WIB.',
            self::TurnLimit => 'Batas percakapan untuk tugas ini tercapai. Coba mandiri dulu atau tanya dosen.',
            self::BlockedAsksSolution => 'Saya tidak bisa memberi jawaban tugas, tapi saya bisa bantu memahami konsepnya.',
            self::BlockedOffTopic => 'Pertanyaan ini di luar tugas ini. Coba tanyakan hal yang terkait tugas.',
            self::Locked => 'Permintaan sebelumnya masih diproses.',
            self::InternalError => 'Terjadi gangguan. Coba lagi.',
        };
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::ProviderBusy, self::InternalError => 503,
            self::QuotaDaily, self::TurnLimit, self::Locked => 429,
            self::BlockedAsksSolution, self::BlockedOffTopic => 200,
        };
    }
}
