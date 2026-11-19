<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'queue_item_id' => [
                'required',
                'integer',
                Rule::exists('queue_items', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')
                ),
            ],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'regex:/^\+?[0-9\s\-()]{6,20}$/'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'queue_item_id.required' => 'Silakan pilih layanan terlebih dahulu.',
            'queue_item_id.exists' => 'Layanan yang dipilih tidak tersedia.',
            'customer_phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda +, -, dan kurung (6 sampai 20 karakter).',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'queue_item_id' => 'Layanan',
            'customer_name' => 'Nama',
            'customer_phone' => 'Nomor telepon',
            'notes' => 'Catatan',
        ];
    }
}
