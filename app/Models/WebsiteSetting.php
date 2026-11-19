<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WebsiteSetting extends Model
{
    private const CACHE_KEY = 'website_settings.all';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, string|null> */
    public static function allValues(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('value', 'key')->all()
        );
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $value = static::allValues()[$key] ?? null;

        return filled($value) ? $value : $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::allValues()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** @param array<string, mixed> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::setValue($key, $value);
        }
    }

    /**
     * Menyimpan sekumpulan pengaturan dalam satu grup. Baris dibuat jika belum ada.
     *
     * @param  array<string, mixed>  $values
     */
    public static function putGroup(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['group' => $group, 'value' => $value]);
        }
    }
}
