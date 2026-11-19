<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ColorSetting extends Model
{
    private const CACHE_KEY = 'color_settings.palette';

    /** Seluruh kunci warna yang dapat diatur. */
    public const KEYS = [
        'primary_color',
        'secondary_color',
        'accent_color',
        'dark_color',
        'background_color',
        'text_color',
    ];

    protected $fillable = [
        'key',
        'label',
        'value',
        'default_value',
    ];

    protected static function booted(): void
    {
        static::saved(fn() => static::flushCache());
        static::deleted(fn() => static::flushCache());
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, array{value: string, default: string}> */
    public static function palette(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->get()->mapWithKeys(fn(ColorSetting $c) => [
                $c->key => ['value' => $c->value, 'default' => $c->default_value],
            ])->all();
        });
    }

    public static function inlineVariables(): string
    {
        $css = '';

        foreach (static::palette() as $key => $color) {
            $hex = preg_match('/^#[0-9A-Fa-f]{6}$/', $color['value']) ? $color['value'] : $color['default'];
            $css .= '--' . str_replace('_', '-', $key) . ':' . $hex . ';';
        }

        return $css;
    }

    /** Blok CSS lengkap, contoh: :root{--primary-color:#F6EB61;...} */
    public static function cssVariables(): string
    {
        return ':root{' . static::inlineVariables() . '}';
    }

    public static function resetToDefault(): void
    {
        static::query()->get()->each(fn(ColorSetting $c) => $c->update(['value' => $c->default_value]));
    }
}
