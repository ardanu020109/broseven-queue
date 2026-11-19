<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebsiteSettingRequest extends FormRequest
{
    private const PHONE_REGEX = '/^\+?[0-9\s\-()]{6,20}$/';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function group(): string
    {
        return (string) $this->route('group');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $imageRules = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000'];
        $link = ['nullable', 'url:http,https', 'max:255'];

        return match ($this->group()) {
            'general' => [
                'site_name' => ['required', 'string', 'max:100'],
                'site_description' => ['nullable', 'string', 'max:500'],
                'site_url' => $link,
                'site_footer' => ['nullable', 'string', 'max:500'],
                'site_copyright' => ['nullable', 'string', 'max:255'],
                'additional_info' => ['nullable', 'string', 'max:1000'],
                'site_logo' => $imageRules,
                'site_favicon' => ['nullable', 'file', 'mimes:png,ico,jpg,jpeg,webp', 'max:512'],
                'remove_site_logo' => ['nullable', 'boolean'],
                'remove_site_favicon' => ['nullable', 'boolean'],
            ],
            'contact' => [
                'contact_address' => ['nullable', 'string', 'max:500'],
                'contact_email' => ['nullable', 'email', 'max:255'],
                'contact_phone' => ['nullable', 'string', 'regex:' . self::PHONE_REGEX],
                'contact_whatsapp' => ['nullable', 'string', 'regex:' . self::PHONE_REGEX],
            ],
            'social' => [
                'social_instagram' => $link,
                'social_facebook' => $link,
                'social_tiktok' => $link,
                'social_youtube' => $link,
                'social_linkedin' => $link,
            ],
            'display' => [
                'display_title' => ['required', 'string', 'max:100'],
                'display_marquee' => ['nullable', 'string', 'max:500'],
                'display_marquee_speed' => ['required', 'integer', 'between:10,120'],
                'display_sound_enabled' => ['nullable', 'boolean'],
                'display_number_size' => ['required', Rule::in(['sm', 'md', 'lg', 'xl'])],
                'display_list_limit' => ['required', 'integer', 'between:1,12'],
                'display_voice_volume' => ['required', 'numeric', 'between:0,1'],
                'display_voice_rate' => ['required', 'numeric', 'between:0.5,1.5'],
                'display_voice_lang' => ['required', Rule::in(['id-ID', 'en-US', 'en-GB', 'ms-MY'])],
                'display_call_template' => ['required', 'string', 'max:200', 'regex:/\{number\}/'],
            ],
            'ticket' => [
                'ticket_paper_size' => ['required', Rule::in(['58mm', '80mm', 'a4'])],
                'ticket_title' => ['required', 'string', 'max:60'],
                'ticket_footer' => ['nullable', 'string', 'max:300'],
                'ticket_extra_info' => ['nullable', 'string', 'max:500'],
                'ticket_show_logo' => ['nullable', 'boolean'],
                'ticket_show_waiting_count' => ['nullable', 'boolean'],
                'ticket_logo' => $imageRules,
                'remove_ticket_logo' => ['nullable', 'boolean'],
            ],
            default => [],
        };
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'contact_phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda +, -, dan kurung (6 sampai 20 karakter).',
            'contact_whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda +, -, dan kurung (6 sampai 20 karakter).',
            'display_call_template.regex' => 'Templat suara harus memuat token {number}.',
            'url' => ':attribute harus berupa tautan http:// atau https:// yang valid.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'site_name' => 'Nama website',
            'site_description' => 'Deskripsi',
            'site_url' => 'Alamat website',
            'site_footer' => 'Footer',
            'site_copyright' => 'Copyright',
            'additional_info' => 'Informasi tambahan',
            'site_logo' => 'Logo',
            'site_favicon' => 'Favicon',
            'contact_address' => 'Alamat',
            'contact_email' => 'Email',
            'contact_phone' => 'Nomor telepon',
            'contact_whatsapp' => 'WhatsApp',
            'social_instagram' => 'Instagram',
            'social_facebook' => 'Facebook',
            'social_tiktok' => 'TikTok',
            'social_youtube' => 'YouTube',
            'social_linkedin' => 'LinkedIn',
            'display_title' => 'Judul display',
            'display_marquee' => 'Pesan berjalan',
            'display_marquee_speed' => 'Kecepatan teks berjalan',
            'display_number_size' => 'Ukuran nomor antrean',
            'display_list_limit' => 'Jumlah antrean ditampilkan',
            'display_voice_volume' => 'Volume',
            'display_voice_rate' => 'Kecepatan bicara',
            'display_voice_lang' => 'Bahasa suara',
            'display_call_template' => 'Templat suara',
            'ticket_paper_size' => 'Ukuran kertas',
            'ticket_title' => 'Judul tiket',
            'ticket_footer' => 'Footer tiket',
            'ticket_extra_info' => 'Informasi tambahan tiket',
            'ticket_logo' => 'Logo tiket',
        ];
    }
}
