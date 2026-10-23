<?php

namespace App\Enums;

/**
 * Status mashinasi: queued → running → done | failed.
 * cancelled — mijoz kutishni to'xtatdi (OpenAI so'rovi baribir to'lanishi mumkin).
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
