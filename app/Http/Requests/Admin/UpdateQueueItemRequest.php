<?php

namespace App\Http\Requests\Admin;

class UpdateQueueItemRequest extends QueueItemRequest
{
    protected function ignoreId(): ?int
    {
        return $this->route('queue_item')?->id;
    }
}
