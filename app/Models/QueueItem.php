<?php

namespace App\Models;

use App\Enums\QueueStatus;
use App\Enums\ResetPeriod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QueueItem extends Model
{
    use SoftDeletes;

    /** Ikon yang boleh dipilih (Bootstrap Icons). */
    public const ICON_CHOICES = [
        'bi-ticket-perforated',
        'bi-cup-hot',
        'bi-cash-coin',
        'bi-box-seam',
        'bi-headset',
        'bi-chat-dots',
        'bi-info-circle',
        'bi-shop',
        'bi-bag',
        'bi-credit-card',
        'bi-receipt',
        'bi-truck',
        'bi-clipboard-check',
        'bi-file-earmark-text',
        'bi-heart-pulse',
        'bi-hospital',
        'bi-mortarboard',
        'bi-bank',
        'bi-building',
        'bi-person-badge',
        'bi-telephone',
        'bi-wrench-adjustable',
        'bi-car-front',
        'bi-cart3',
    ];

    protected $fillable = [
        'name',
        'slug',
        'prefix',
        'description',
        'icon',
        'logo',
        'color',
        'number_format',
        'number_padding',
        'reset_period',
        'operating_hours',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'reset_period' => ResetPeriod::class,
            'operating_hours' => 'array',
            'is_active' => 'boolean',
            'number_padding' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** URL logo unggahan, atau null jika belum ada. */
    public function logoUrl(): ?string
    {
        return $this->logo ? asset('storage/' . ltrim($this->logo, '/')) : null;
    }

    protected static function booted(): void
    {
        static::creating(function (QueueItem $item) {
            if (blank($item->slug)) {
                $item->slug = static::uniqueSlug((string) $item->name);
            }
        });

        static::saving(function (QueueItem $item) {
            $item->prefix = Str::upper(trim((string) $item->prefix));
        });
    }

    /* ---------------------------------------------------------------
     | Relasi
     * ------------------------------------------------------------- */

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    /** Antrean yang sedang dipanggil (maksimal satu, dijamin oleh DB). */
    public function activeQueue(): HasOne
    {
        return $this->hasOne(Queue::class)->where('status', QueueStatus::Called->value);
    }

    /* ---------------------------------------------------------------
     | Scope
     * ------------------------------------------------------------- */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /* ---------------------------------------------------------------
     | Helper
     * ------------------------------------------------------------- */

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'layanan';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /** Saran prefix dari inisial nama: "Cireng Viral" => CV, "Konsultasi" => KON. */
    public static function suggestPrefix(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9\s]/', '', Str::ascii($name)) ?? '';
        $words = array_values(array_filter(preg_split('/\s+/', trim($clean)) ?: []));

        if (count($words) === 0) {
            return '';
        }

        if (count($words) === 1) {
            return Str::upper(Str::substr($words[0], 0, 3));
        }

        return Str::upper(collect($words)->take(4)->map(fn($w) => Str::substr($w, 0, 1))->implode(''));
    }

    /**
     * Membentuk nomor antrean dari urutan, mengikuti konfigurasi layanan.
     * Token: {PREFIX} {NUMBER} {YYYY} {YY} {MM} {DD}
     */
    public function formatNumber(int $sequence, ?CarbonInterface $date = null): string
    {
        $date ??= now();
        $number = str_pad((string) $sequence, max(1, (int) $this->number_padding), '0', STR_PAD_LEFT);

        return strtr($this->number_format ?: '{PREFIX}{NUMBER}', [
            '{PREFIX}' => (string) $this->prefix,
            '{NUMBER}' => $number,
            '{YYYY}' => $date->format('Y'),
            '{YY}' => $date->format('y'),
            '{MM}' => $date->format('m'),
            '{DD}' => $date->format('d'),
        ]);
    }

    /**
     * Jam operasional disimpan sebagai: {"enabled": true, "open": "08:00", "close": "17:00"}.
     * Jika tidak diaktifkan, layanan dianggap buka terus.
     */
    public function isOpenAt(?CarbonInterface $at = null): bool
    {
        $hours = $this->operating_hours;

        if (empty($hours) || empty($hours['enabled']) || empty($hours['open']) || empty($hours['close'])) {
            return true;
        }

        $time = ($at ?? now())->format('H:i');
        $open = $hours['open'];
        $close = $hours['close'];

        if ($open <= $close) {
            return $time >= $open && $time <= $close;
        }

        // Jam operasional melewati tengah malam.
        return $time >= $open || $time <= $close;
    }

    /** Kunci periode reset yang berlaku pada waktu tertentu (default: sekarang). */
    public function currentPeriodKey(?CarbonInterface $at = null): string
    {
        return $this->reset_period->periodKey($at ?? now());
    }

    /** Teks jam operasional untuk pengunjung. */
    public function hoursLabel(): string
    {
        $hours = $this->operating_hours;

        if (empty($hours) || empty($hours['enabled']) || empty($hours['open']) || empty($hours['close'])) {
            return 'Setiap saat';
        }

        return $hours['open'] . ' - ' . $hours['close'];
    }

    /** Warna layanan yang dijamin berformat #RRGGBB, aman dipakai pada atribut style. */
    public function safeColor(): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $this->color) === 1 ? $this->color : '#F9C74F';
    }
}
