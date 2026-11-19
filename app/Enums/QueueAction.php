<?php

namespace App\Enums;

enum QueueAction: string
{
    case Called = 'called';
    case Recalled = 'recalled';
    case Skipped = 'skipped';
    case Restored = 'restored';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Called => 'Dipanggil',
            self::Recalled => 'Dipanggil Lagi',
            self::Skipped => 'Di-skip',
            self::Restored => 'Dipanggil Kembali (dari skip)',
            self::Completed => 'Diselesaikan',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
