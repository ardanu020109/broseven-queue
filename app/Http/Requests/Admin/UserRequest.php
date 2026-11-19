<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

abstract class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** ID user yang sedang diubah (null saat menambah). */
    protected function ignoreId(): ?int
    {
        return null;
    }

    /** Password wajib saat menambah, opsional saat mengubah. @return array<int, string> */
    abstract protected function presenceRule(): array;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:50',
                Rule::unique('users', 'username')->ignore($this->ignoreId()),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->ignoreId()),
            ],
            'password' => [
                ...$this->presenceRule(),
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_photo' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'Nama',
            'username' => 'Username',
            'email' => 'Email',
            'password' => 'Password',
            'photo' => 'Foto profil',
        ];
    }

    /** Data siap simpan; password dihilangkan jika tidak diisi. */
    public function userData(): array
    {
        $data = $this->validated();

        $result = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'is_active' => $this->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $result['password'] = $data['password'];
        }

        return $result;
    }
}
