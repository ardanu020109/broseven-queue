<?php

namespace App\Events;

class QueueRecalled extends QueueBroadcastEvent
{
    protected function type(): string
    {
        return 'recalled';
    }
}
