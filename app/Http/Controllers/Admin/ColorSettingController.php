<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ColorSettingRequest;
use App\Models\ActivityLog;
use App\Models\ColorSetting;
use App\Support\Contrast;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ColorSettingController extends Controller
{
    public function edit(): View
    {
        $colors = ColorSetting::query()->orderBy('id')->get()->keyBy('key');

        return view('admin.settings.colors.edit', compact('colors'));
    }

    public function update(ColorSettingRequest $request): RedirectResponse
    {
        $saved = [];

        foreach (ColorSetting::KEYS as $key) {
            $value = strtoupper($request->validated($key));

            ColorSetting::query()->where('key', $key)->firstOrFail()->update(['value' => $value]);

            $saved[$key] = $value;
        }

        ActivityLog::record('colors_updated', 'Pengaturan warna diperbarui.');

        $redirect = redirect()
            ->route('admin.settings.colors.edit')
            ->with('success', 'Warna berhasil disimpan dan langsung berlaku di seluruh website.');

        $warnings = Contrast::warnings($saved);

        return $warnings === [] ? $redirect : $redirect->with('warning', $warnings);
    }

    public function reset(): RedirectResponse
    {
        ColorSetting::resetToDefault();

        ActivityLog::record('colors_reset', 'Warna dikembalikan ke pengaturan default.');

        return redirect()
            ->route('admin.settings.colors.edit')
            ->with('success', 'Warna dikembalikan ke default.');
    }
}
