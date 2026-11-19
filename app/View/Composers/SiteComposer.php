<?php

namespace App\View\Composers;

use App\Models\ColorSetting;
use App\Models\WebsiteSetting;
use Illuminate\View\View;

class SiteComposer
{
    public function compose(View $view): void
    {
        $get = fn (string $key, mixed $default = null) => WebsiteSetting::getValue($key, $default);
        $file = fn (?string $path) => $path ? asset('storage/' . ltrim($path, '/')) : null;

        $socialMeta = [
            'instagram' => ['Instagram', 'bi-instagram'],
            'facebook' => ['Facebook', 'bi-facebook'],
            'tiktok' => ['TikTok', 'bi-tiktok'],
            'youtube' => ['YouTube', 'bi-youtube'],
            'linkedin' => ['LinkedIn', 'bi-linkedin'],
        ];

        // Hanya tautan http/https yang ditampilkan (mencegah skema berbahaya seperti javascript:).
        $socials = collect($socialMeta)
            ->map(fn (array $meta, string $key) => [
                'label' => $meta[0],
                'icon' => $meta[1],
                'url' => (string) $get('social_' . $key, ''),
            ])
            ->filter(fn (array $social) => preg_match('#^https?://#i', $social['url']) === 1)
            ->values()
            ->all();

        $whatsapp = preg_replace('/\D+/', '', (string) $get('contact_whatsapp', '')) ?? '';
        if (str_starts_with($whatsapp, '0')) {
            $whatsapp = '62' . substr($whatsapp, 1);
        }

        $site = (object) [
            'name' => $get('site_name', config('app.name')),
            'description' => $get('site_description'),
            'url' => $get('site_url'),
            'logo_url' => $file($get('site_logo')),
            'favicon_url' => $file($get('site_favicon')),
            'footer' => $get('site_footer'),
            'copyright' => $get('site_copyright', '© ' . date('Y') . ' ' . $get('site_name', config('app.name'))),
            'additional_info' => $get('additional_info'),
            'address' => $get('contact_address'),
            'email' => $get('contact_email'),
            'phone' => $get('contact_phone'),
            'whatsapp' => $get('contact_whatsapp'),
            'whatsapp_url' => $whatsapp !== '' ? 'https://wa.me/' . $whatsapp : null,
            'socials' => $socials,
        ];

        $view->with('site', $site)->with('themeStyle', ColorSetting::inlineVariables());
    }
}
