<?php

namespace Database\Seeders;

use App\Models\ColorSetting;
use Illuminate\Database\Seeder;

class ColorSettingSeeder extends Seeder
{
    public function run(): void
    {
        // key => [label, hex default]
        $colors = [
            'primary_color' => ['Primary Color', '#F6EB61'],
            'secondary_color' => ['Secondary Color', '#A8A8A8'],
            'accent_color' => ['Accent Color', '#F9C74F'],
            'dark_color' => ['Dark Color', '#6D6D6D'],
            'background_color' => ['Background Color', '#F9F9F9'],
            'text_color' => ['Text Color', '#292929'],
        ];

        foreach ($colors as $key => [$label, $hex]) {
            $color = ColorSetting::firstOrNew(['key' => $key]);
            $color->label = $label;
            $color->default_value = $hex;

            if (! $color->exists) {
                $color->value = $hex;
            }

            $color->save();
        }

        ColorSetting::flushCache();
    }
}
