<?php

namespace App\Events;

use App\Models\Queue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

abstract class QueueBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Queue $queue)
    {
        $this->queue->loadMissing('item');
    }

    /** Jenis perubahan: created, called, recalled, skipped, completed. */
    abstract protected function type(): string;

    /** Channel publik: hanya informasi yang memang tampil di layar display. */
    public function broadcastOn(): array
    {
        return [new Channel('queue-display')];
    }

    public function broadcastAs(): string
    {
        return 'queue.' . $this->type();
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type(),
            'number' => $this->queue->queue_number,
            'service_id' => $this->queue->queue_item_id,
            'service' => $this->queue->item?->name,
            'counter' => $this->queue->counter,
            'status' => $this->queue->status->value,
            'at' => now()->toIso8601String(),
        ];
    }
}
