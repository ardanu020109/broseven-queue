<?php

namespace App\Support;

class Contrast
{
    /** Rasio kontras WCAG antara dua warna #RRGGBB (1 sampai 21). */
    public static function ratio(string $hexA, string $hexB): float
    {
        $a = self::luminance($hexA);
        $b = self::luminance($hexB);

        [$lighter, $darker] = $a >= $b ? [$a, $b] : [$b, $a];

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Peringatan kontras untuk palet. Kunci wajib: dark_color, text_color,
     * background_color, primary_color, accent_color.
     *
     * @param  array<string, string>  $colors
     * @return array<int, string>
     */
    public static function warnings(array $colors): array
    {
        $checks = [
            ['Teks putih pada Dark Color (sidebar dan footer)', '#FFFFFF', $colors['dark_color'], 4.5],
            ['Teks pada Background Color', $colors['text_color'], $colors['background_color'], 4.5],
            ['Teks pada tombol Primary Color', $colors['text_color'], $colors['primary_color'], 4.5],
            ['Teks pada Accent Color (notifikasi)', $colors['text_color'], $colors['accent_color'], 4.5],
            ['Nomor Primary Color pada layar display', $colors['primary_color'], self::displayBackground($colors['dark_color']), 3.0],
        ];

        $warnings = [];

        foreach ($checks as [$label, $foreground, $background, $minimum]) {
            $ratio = self::ratio($foreground, $background);

            if ($ratio < $minimum) {
                $warnings[] = sprintf(
                    'Kontras rendah: %s hanya %.1f:1 (disarankan minimal %.1f:1).',
                    $label,
                    $ratio,
                    $minimum
                );
            }
        }

        return $warnings;
    }

    /** Latar layar display: 40% Dark Color dicampur hitam (sama dengan CSS display). */
    public static function displayBackground(string $darkHex): string
    {
        $hex = ltrim($darkHex, '#');

        return sprintf(
            '#%02X%02X%02X',
            (int) round(hexdec(substr($hex, 0, 2)) * 0.4),
            (int) round(hexdec(substr($hex, 2, 2)) * 0.4),
            (int) round(hexdec(substr($hex, 4, 2)) * 0.4)
        );
    }

    private static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        $channels = array_map(function ($channel) {
            $value = $channel / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ]);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
