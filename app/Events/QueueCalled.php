<?php

namespace App\Events;

class QueueCalled extends QueueBroadcastEvent
{
    protected function type(): string
    {
        return 'called';
    }
}
