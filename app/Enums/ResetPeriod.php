<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum ResetPeriod: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Never = 'never';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Setiap hari',
            self::Weekly => 'Setiap minggu',
            self::Monthly => 'Setiap bulan',
            self::Yearly => 'Setiap tahun',
            self::Never => 'Tidak pernah (berlanjut terus)',
        };
    }

    /** Kunci periode: nomor berurutan dihitung ulang dari 1 saat kunci berubah. */
    public function periodKey(CarbonInterface $date): string
    {
        return match ($this) {
            self::Daily => $date->format('Y-m-d'),
            self::Weekly => $date->format('o-\WW'),
            self::Monthly => $date->format('Y-m'),
            self::Yearly => $date->format('Y'),
            self::Never => 'all',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
