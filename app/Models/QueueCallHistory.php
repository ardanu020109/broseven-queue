<?php

namespace App\Models;

use App\Enums\QueueAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueCallHistory extends Model
{
    protected $fillable = [
        'queue_id',
        'user_id',
        'action',
        'description',
        'called_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => QueueAction::class,
            'called_at' => 'datetime',
        ];
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
