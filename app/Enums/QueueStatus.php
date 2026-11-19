<?php

namespace App\Enums;

enum QueueStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case Skipped = 'skipped';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu',
            self::Called => 'Sedang Dipanggil',
            self::Skipped => 'Di-skip',
            self::Completed => 'Selesai',
        };
    }

    /** Dipakai sebagai suffix class CSS, contoh: badge-waiting */
    public function badge(): string
    {
        return $this->value;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
