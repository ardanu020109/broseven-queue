<?php

namespace Tests\Feature;

use App\Models\ColorSetting;
use App\Models\User;
use App\Support\Contrast;
use Database\Seeders\ColorSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColorSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(ColorSettingSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'email' => 'admin@example.test',
            'password' => 'Secret123',
            'is_active' => true,
        ]);
    }

    private function palette(array $overrides = []): array
    {
        return array_merge([
            'primary_color' => '#aa11cc',
            'secondary_color' => '#a8a8a8',
            'accent_color' => '#f9c74f',
            'dark_color' => '#6d6d6d',
            'background_color' => '#f9f9f9',
            'text_color' => '#292929',
        ], $overrides);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('admin.settings.colors.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.settings.colors.update'), $this->palette())->assertRedirect(route('login'));
        $this->post(route('admin.settings.colors.reset'))->assertRedirect(route('login'));
    }

    public function test_page_lists_all_six_colors(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.colors.edit'))
            ->assertOk()
            ->assertSee('Primary Color')
            ->assertSee('Secondary Color')
            ->assertSee('Accent Color')
            ->assertSee('Dark Color')
            ->assertSee('Background Color')
            ->assertSee('Text Color')
            ->assertSee('Reset ke Warna Default')
            ->assertSee('Preview Perubahan');
    }

    public function test_update_stores_uppercase_values_and_applies_them_everywhere(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.colors.update'), $this->palette())
            ->assertRedirect(route('admin.settings.colors.edit'))
            ->assertSessionHas('success');

        $this->assertSame('#AA11CC', ColorSetting::where('key', 'primary_color')->value('value'));
        $this->assertSame('#AA11CC', ColorSetting::palette()['primary_color']['value']);

        $this->get(route('home'))->assertSee('--primary-color:#AA11CC', false);
        $this->get(route('login'))->assertSee('--primary-color:#AA11CC', false);
        $this->get(route('display.index'))->assertSee('--primary-color:#AA11CC', false);
        $this->assertDatabaseHas('activity_logs', ['action' => 'colors_updated']);
    }

    public function test_invalid_colors_are_rejected_and_nothing_changes(): void
    {
        foreach (['red', '#12345', '#GGGGGG', '', 'rgb(1,2,3)'] as $bad) {
            $this->actingAs($this->admin)
                ->put(route('admin.settings.colors.update'), $this->palette(['primary_color' => $bad]))
                ->assertSessionHasErrors('primary_color');
        }

        $this->assertSame('#F6EB61', ColorSetting::where('key', 'primary_color')->value('value'));
    }

    public function test_reset_restores_default_colors(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.colors.update'), $this->palette());

        $this->actingAs($this->admin)
            ->post(route('admin.settings.colors.reset'))
            ->assertRedirect(route('admin.settings.colors.edit'))
            ->assertSessionHas('success');

        $this->assertSame('#F6EB61', ColorSetting::where('key', 'primary_color')->value('value'));
        $this->assertSame('#6D6D6D', ColorSetting::where('key', 'dark_color')->value('value'));
        $this->get(route('home'))->assertSee('--primary-color:#F6EB61', false);
        $this->assertDatabaseHas('activity_logs', ['action' => 'colors_reset']);
    }

    public function test_low_contrast_palette_is_saved_but_warned_about(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.colors.update'), $this->palette(['dark_color' => '#dddddd']))
            ->assertSessionHas('success')
            ->assertSessionHas('warning', fn (array $warnings) => count($warnings) >= 1
                && str_contains(implode(' ', $warnings), 'Dark Color'));

        $this->assertSame('#DDDDDD', ColorSetting::where('key', 'dark_color')->value('value'));
    }

    public function test_default_palette_has_no_contrast_warnings(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.colors.update'), $this->palette([
                'primary_color' => '#F6EB61', 'secondary_color' => '#A8A8A8', 'accent_color' => '#F9C74F',
                'dark_color' => '#6D6D6D', 'background_color' => '#F9F9F9', 'text_color' => '#292929',
            ]))
            ->assertSessionMissing('warning');
    }

    public function test_contrast_ratio_matches_wcag_reference_values(): void
    {
        $this->assertEqualsWithDelta(21.0, Contrast::ratio('#000000', '#FFFFFF'), 0.01);
        $this->assertEqualsWithDelta(1.0, Contrast::ratio('#777777', '#777777'), 0.001);
        $this->assertEqualsWithDelta(Contrast::ratio('#FFFFFF', '#6D6D6D'), Contrast::ratio('#6D6D6D', '#FFFFFF'), 0.0001);
        $this->assertSame('#2B2B2B', Contrast::displayBackground('#6D6D6D'));
    }
}
