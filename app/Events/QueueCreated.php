<?php

namespace App\Events;

class QueueCreated extends QueueBroadcastEvent
{
    protected function type(): string
    {
        return 'created';
    }
}
