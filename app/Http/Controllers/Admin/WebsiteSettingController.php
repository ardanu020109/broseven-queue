<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteSettingRequest;
use App\Models\ActivityLog;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WebsiteSettingController extends Controller
{
    /** Tab yang tersedia: kunci => label. */
    public const TABS = [
        'general' => 'General',
        'contact' => 'Contact',
        'social' => 'Social Media',
        'display' => 'Display',
        'ticket' => 'Ticket',
    ];

    /** Kolom yang disimpan pada tiap grup (selain berkas). */
    private const FIELDS = [
        'general' => ['site_name', 'site_description', 'site_url', 'site_footer', 'site_copyright', 'additional_info'],
        'contact' => ['contact_address', 'contact_email', 'contact_phone', 'contact_whatsapp'],
        'social' => ['social_instagram', 'social_facebook', 'social_tiktok', 'social_youtube', 'social_linkedin'],
        'display' => [
            'display_title', 'display_marquee', 'display_marquee_speed', 'display_sound_enabled',
            'display_number_size', 'display_list_limit', 'display_voice_volume', 'display_voice_rate',
            'display_voice_lang', 'display_call_template',
        ],
        'ticket' => ['ticket_paper_size', 'ticket_title', 'ticket_footer', 'ticket_extra_info', 'ticket_show_logo', 'ticket_show_waiting_count'],
    ];

    private const BOOLEANS = ['display_sound_enabled', 'ticket_show_logo', 'ticket_show_waiting_count'];

    private const FILES = [
        'general' => ['site_logo', 'site_favicon'],
        'ticket' => ['ticket_logo'],
    ];

    public function edit(Request $request): View
    {
        $tab = (string) $request->query('tab', 'general');

        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'general';
        }

        return view('admin.settings.website.edit', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'values' => WebsiteSetting::allValues(),
        ]);
    }

    public function update(WebsiteSettingRequest $request, string $group): RedirectResponse
    {
        $data = $request->validated();
        $values = [];

        foreach (self::FIELDS[$group] as $key) {
            $values[$key] = in_array($key, self::BOOLEANS, true)
                ? ($request->boolean($key) ? '1' : '0')
                : ($data[$key] ?? null);
        }

        foreach (self::FILES[$group] ?? [] as $key) {
            $current = WebsiteSetting::getValue($key);

            if ($request->hasFile($key)) {
                $values[$key] = $request->file($key)->store('branding', 'public');
                $this->deleteFile($current);
            } elseif ($request->boolean('remove_' . $key)) {
                $values[$key] = null;
                $this->deleteFile($current);
            }
        }

        WebsiteSetting::putGroup($group, $values);

        ActivityLog::record('settings_updated', 'Pengaturan website (' . self::TABS[$group] . ') diperbarui.');

        return redirect()
            ->route('admin.settings.website.edit', ['tab' => $group])
            ->with('success', 'Pengaturan ' . self::TABS[$group] . ' berhasil disimpan.');
    }

    /** Hanya berkas di folder branding yang boleh dihapus. */
    private function deleteFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'branding/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
