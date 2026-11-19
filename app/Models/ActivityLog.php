<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
    ];

    /** Ikon Bootstrap Icons untuk jenis aktivitas. */
    public function icon(): string
    {
        return match ($this->action) {
            'queue_created' => 'bi-ticket-perforated',
            'queue_called' => 'bi-megaphone',
            'queue_recalled' => 'bi-arrow-repeat',
            'queue_skipped' => 'bi-skip-forward',
            'queue_restored' => 'bi-arrow-counterclockwise',
            'queue_completed' => 'bi-check2-circle',
            'queue_item_created' => 'bi-plus-circle',
            'queue_item_updated' => 'bi-pencil-square',
            'queue_item_deleted' => 'bi-trash',
            'queue_item_toggled' => 'bi-toggle-on',
            'queue_item_reordered' => 'bi-arrow-down-up',
            'user_created' => 'bi-person-plus',
            'user_updated' => 'bi-person-gear',
            'user_deleted' => 'bi-person-x',
            'user_toggled' => 'bi-person-check',
            'settings_updated' => 'bi-sliders',
            'colors_updated' => 'bi-palette',
            'colors_reset' => 'bi-arrow-counterclockwise',
            'login' => 'bi-box-arrow-in-right',
            'logout' => 'bi-box-arrow-right',
            'profile_updated' => 'bi-person-gear',
            'password_changed' => 'bi-shield-lock',
            default => 'bi-activity',
        };
    }

    /** Nada warna (sama dengan nada pada komponen stat-card). */
    public function tone(): string
    {
        return match ($this->action) {
            'queue_created', 'queue_item_created', 'user_created' => 'accent',
            'queue_called', 'queue_restored', 'queue_completed' => 'success',
            'queue_skipped', 'queue_item_deleted', 'user_deleted' => 'danger',
            'login', 'logout', 'password_changed', 'queue_item_reordered' => 'dark',
            default => 'primary',
        };
    }

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** Mencatat satu aktivitas admin. */
    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?int $userId = null
    ): self {
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }
}
