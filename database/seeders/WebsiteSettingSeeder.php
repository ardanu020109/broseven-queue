<?php

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

class WebsiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $year = date('Y');

        // [group, key, value, type]
        $defaults = [
            // General
            ['general', 'site_name', 'Broseven Queue', 'text'],
            ['general', 'site_description', 'Sistem manajemen antrean modern: ambil nomor, pantau panggilan, dan layani pelanggan tanpa berdesakan.', 'textarea'],
            ['general', 'site_url', 'http://127.0.0.1:8000', 'url'],
            ['general', 'site_logo', '', 'image'],
            ['general', 'site_favicon', '', 'image'],
            ['general', 'site_footer', 'Broseven Queue, solusi antrean yang tertib dan efisien.', 'textarea'],
            ['general', 'site_copyright', '© ' . $year . ' Broseven Queue. Seluruh hak cipta dilindungi.', 'text'],
            ['general', 'additional_info', '', 'textarea'],

            // Contact
            ['contact', 'contact_address', '', 'textarea'],
            ['contact', 'contact_email', '', 'email'],
            ['contact', 'contact_phone', '', 'text'],
            ['contact', 'contact_whatsapp', '', 'text'],

            // Social media
            ['social', 'social_instagram', '', 'url'],
            ['social', 'social_facebook', '', 'url'],
            ['social', 'social_tiktok', '', 'url'],
            ['social', 'social_youtube', '', 'url'],
            ['social', 'social_linkedin', '', 'url'],

            // Display
            ['display', 'display_title', 'Display Antrean', 'text'],
            ['display', 'display_marquee', 'Selamat datang. Harap menunggu hingga nomor antrean Anda dipanggil.', 'textarea'],
            ['display', 'display_marquee_speed', '40', 'number'],
            ['display', 'display_sound_enabled', '1', 'boolean'],
            ['display', 'display_number_size', 'lg', 'text'],
            ['display', 'display_list_limit', '5', 'number'],
            ['display', 'display_voice_volume', '1', 'number'],
            ['display', 'display_voice_rate', '0.9', 'number'],
            ['display', 'display_voice_lang', 'id-ID', 'text'],
            ['display', 'display_call_template', 'Nomor antrean {number}, silakan menuju loket pelayanan.', 'text'],

            // Ticket
            ['ticket', 'ticket_paper_size', '80mm', 'text'],
            ['ticket', 'ticket_logo', '', 'image'],
            ['ticket', 'ticket_show_logo', '1', 'boolean'],
            ['ticket', 'ticket_show_waiting_count', '1', 'boolean'],
            ['ticket', 'ticket_title', 'TIKET ANTREAN', 'text'],
            ['ticket', 'ticket_footer', 'Terima kasih. Harap menunggu hingga nomor Anda dipanggil.', 'textarea'],
            ['ticket', 'ticket_extra_info', '', 'textarea'],
        ];

        // firstOrCreate: pengaturan yang sudah diubah admin tidak tertimpa saat seeder dijalankan ulang.
        foreach ($defaults as [$group, $key, $value, $type]) {
            WebsiteSetting::firstOrCreate(
                ['key' => $key],
                ['group' => $group, 'value' => $value, 'type' => $type]
            );
        }

        WebsiteSetting::flushCache();
    }
}
