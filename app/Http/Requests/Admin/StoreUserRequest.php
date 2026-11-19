<?php

namespace App\Http\Requests\Admin;

class StoreUserRequest extends UserRequest
{
    protected function presenceRule(): array
    {
        return ['required'];
    }
}
