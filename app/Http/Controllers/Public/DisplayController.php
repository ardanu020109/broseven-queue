<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\QueueItem;
use App\Models\WebsiteSetting;
use App\Services\DisplayDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisplayController extends Controller
{
    public function index(Request $request, DisplayDataService $data): View
    {
        $only = $this->resolveService($request);
        $config = $this->config($only);

        return view('display.index', [
            'snapshot' => $data->snapshot($only, $config['list_limit']),
            'config' => $config,
            'only' => $only,
            'dataUrl' => route('display.data', $only ? ['layanan' => $only->slug] : []),
        ]);
    }

    public function data(Request $request, DisplayDataService $data): JsonResponse
    {
        $only = $this->resolveService($request);

        return response()
            ->json($data->snapshot($only, $this->config($only)['list_limit']))
            ->header('Cache-Control', 'no-store');
    }

    /** ?layanan=slug membatasi layar pada satu layanan; slug tidak dikenal menghasilkan 404. */
    private function resolveService(Request $request): ?QueueItem
    {
        $slug = (string) $request->query('layanan', '');

        return $slug === '' ? null : QueueItem::query()->where('slug', $slug)->firstOrFail();
    }

    /** Pengaturan tampilan; setiap nilai dibatasi ke rentang aman. @return array<string, mixed> */
    private function config(?QueueItem $only): array
    {
        $get = fn (string $key, mixed $default = null) => WebsiteSetting::getValue($key, $default);
        $clamp = fn (float $value, float $min, float $max) => max($min, min($max, $value));

        $size = (string) $get('display_number_size', 'lg');
        if (! in_array($size, ['sm', 'md', 'lg', 'xl'], true)) {
            $size = 'lg';
        }

        $lang = (string) $get('display_voice_lang', 'id-ID');
        if (! preg_match('/^[a-z]{2}-[A-Z]{2}$/', $lang)) {
            $lang = 'id-ID';
        }

        return [
            'title' => (string) $get('display_title', 'Display Antrean'),
            'marquee' => (string) $get('display_marquee', ''),
            'marquee_speed' => $clamp((float) $get('display_marquee_speed', 40), 10, 120),
            'sound_enabled' => WebsiteSetting::getBool('display_sound_enabled', true),
            'volume' => $clamp((float) $get('display_voice_volume', 1), 0, 1),
            'rate' => $clamp((float) $get('display_voice_rate', 0.9), 0.5, 1.5),
            'lang' => $lang,
            'template' => (string) $get('display_call_template', 'Nomor antrean {number}, silakan menuju loket pelayanan.'),
            'number_size' => $size,
            'list_limit' => (int) $clamp((float) $get('display_list_limit', 5), 1, 12),
            'timezone' => config('app.timezone'),
            'service_id' => $only?->id,
        ];
    }
}
