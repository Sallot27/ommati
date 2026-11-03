<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TajweedTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_rule_colour_of_the_font_is_recoloured_and_explained(): void
    {
        $entries = [];
        foreach (config('tajweed.rules') as $rule) {
            $this->assertNotEmpty($rule['rule']);
            $this->assertNotEmpty($rule['how']);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $rule['light']);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $rule['dark']);
            $entries = array_merge($entries, $rule['entries']);
        }
        sort($entries);
        // CPAL entries 1–9 and 15 are the tajweed colours of the KFGQPC V4 tajweed fonts
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9, 15], $entries);
    }

    public function test_page_data_carries_a_colour_glyph_for_every_word(): void
    {
        $words = $withGlyph = 0;
        foreach (glob(public_path('mushaf-data/pages/*.json')) as $file) {
            foreach (json_decode(file_get_contents($file), true)['lines'] as $line) {
                foreach ($line as $w) {
                    if (in_array($w['t'], ['w', 'e'], true) && isset($w['k'])) {
                        $words++;
                        $withGlyph += isset($w['v']) ? 1 : 0;
                    }
                }
            }
        }
        $this->assertGreaterThan(83000, $words);
        // ~0.4% (sajdah signs and words whose colour glyph could not be verified) stay in the plain font
        $this->assertGreaterThan(0.995, $withGlyph / $words);
    }

    public function test_mushaf_page_offers_tajweed(): void
    {
        $this->actingAs(User::factory()->create())->get('/mushaf/2')
            ->assertOk()->assertSee('تجويد')
            ->assertViewHas('cfg', fn ($c) => isset($c['tajweed']['rules']['idgham']) && $c['tajweed']['fontUrl'] === rtrim(config('tajweed.font_url'), '/'));
        $this->assertStringContainsString('mushaf-fonts-qpc-v4-tajweed', file_get_contents(config_path('tajweed.php')));
    }
}
