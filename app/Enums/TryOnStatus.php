<?php

namespace App\Enums;

/**
 * Status machine: queued → running → done | failed.
 * cancelled — the customer stopped waiting (the OpenAI call may still be billed).
 */
enum TryOnStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isFinished(): bool
    {
        return in_array($this, [self::Done, self::Failed, self::Cancelled], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
