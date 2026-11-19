<?php

namespace App\Events;

class QueueSkipped extends QueueBroadcastEvent
{
    protected function type(): string
    {
        return 'skipped';
    }
}
