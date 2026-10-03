<?php

namespace App\Services\Ai;

class OutputGuardResult
{
    public const STATUS_SAFE = 'safe';

    public const STATUS_REJECT = 'reject';

    public const STATUS_DOUBTFUL = 'doubtful';

    public function __construct(
        public readonly string $status,
        public readonly string $verdict,
        public readonly string $reason
    ) {}

    public function isSafe(): bool
    {
        return $this->status === self::STATUS_SAFE;
    }

    public function isReject(): bool
    {
        return $this->status === self::STATUS_REJECT;
    }

    public function isDoubtful(): bool
    {
        return $this->status === self::STATUS_DOUBTFUL;
    }
}
