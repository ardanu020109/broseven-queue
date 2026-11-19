<?php

namespace App\Models;

use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends Model
{
    protected $fillable = [
        'queue_item_id',
        'queue_number',
        'sequence_number',
        'period_key',
        'customer_name',
        'customer_phone',
        'notes',
        'status',
        'counter',
        'issued_at',
        'called_at',
        'recalled_at',
        'skipped_at',
        'completed_at',
        'called_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => QueueStatus::class,
            'sequence_number' => 'integer',
            'issued_at' => 'datetime',
            'called_at' => 'datetime',
            'recalled_at' => 'datetime',
            'skipped_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /* ---------------------------------------------------------------
     | Relasi
     * ------------------------------------------------------------- */

    public function item(): BelongsTo
    {
        return $this->belongsTo(QueueItem::class, 'queue_item_id')->withTrashed();
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(QueueCallHistory::class)->orderByDesc('called_at')->orderByDesc('id');
    }

    /* ---------------------------------------------------------------
     | Scope
     * ------------------------------------------------------------- */

    public function scopeWithStatus($query, QueueStatus $status)
    {
        return $query->where('status', $status->value);
    }

    public function scopeIssuedToday($query)
    {
        return $query->whereDate('issued_at', now()->toDateString());
    }

    /* ---------------------------------------------------------------
     | Helper
     * ------------------------------------------------------------- */

    /** Jumlah antrean menunggu yang berada di depan nomor ini (layanan dan periode sama). */
    public function waitingAheadCount(): int
    {
        return static::query()
            ->where('queue_item_id', $this->queue_item_id)
            ->where('period_key', $this->period_key)
            ->where('status', QueueStatus::Waiting->value)
            ->where('sequence_number', '<', $this->sequence_number)
            ->count();
    }

    /** Data ringkas untuk dialog detail di tabel admin (relasi item harus sudah dimuat). */
    public function detailPayload(): array
    {
        $format = fn($date) => $date?->translatedFormat('d M Y, H:i:s');

        return [
            'number' => $this->queue_number,
            'service' => $this->item?->name,
            'name' => $this->customer_name,
            'phone' => $this->customer_phone,
            'notes' => $this->notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'counter' => $this->counter,
            'issued_at' => $format($this->issued_at),
            'called_at' => $format($this->called_at),
            'skipped_at' => $format($this->skipped_at),
            'completed_at' => $format($this->completed_at),
        ];
    }
}
