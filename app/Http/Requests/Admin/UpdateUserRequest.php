<?php

namespace App\Http\Requests\Admin;

class UpdateUserRequest extends UserRequest
{
    protected function ignoreId(): ?int
    {
        return $this->route('user')?->id;
    }

    protected function presenceRule(): array
    {
        return ['nullable'];
    }
}
