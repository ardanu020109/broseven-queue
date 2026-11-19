<?php

namespace App\Http\Requests\Admin;

use App\Models\ColorSetting;
use Illuminate\Foundation\Http\FormRequest;

class ColorSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $rules = [];

        foreach (ColorSetting::KEYS as $key) {
            $rules[$key] = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            '*.regex' => ':attribute harus berformat #RRGGBB (contoh #F6EB61).',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'primary_color' => 'Primary Color',
            'secondary_color' => 'Secondary Color',
            'accent_color' => 'Accent Color',
            'dark_color' => 'Dark Color',
            'background_color' => 'Background Color',
            'text_color' => 'Text Color',
        ];
    }
}
