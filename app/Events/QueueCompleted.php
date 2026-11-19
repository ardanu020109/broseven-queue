<?php

namespace App\Events;

class QueueCompleted extends QueueBroadcastEvent
{
    protected function type(): string
    {
        return 'completed';
    }
}
