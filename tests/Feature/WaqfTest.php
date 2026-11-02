<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Quran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaqfTest extends TestCase
{
    use RefreshDatabase;

    public function test_mushaf_pages_carry_every_waqf_mark_of_the_text(): void
    {
        $expected = ['ۚ' => 2083, 'ۖ' => 1651, 'ۗ' => 511, 'ۘ' => 21, 'ۛ' => 6, 'ۜ' => 5];
        $found = array_fill_keys(array_keys($expected), 0);
        foreach (glob(public_path('mushaf-data/pages/*.json')) as $file) {
            foreach (json_decode(file_get_contents($file), true)['lines'] as $line) {
                foreach ($line as $w) {
                    foreach (mb_str_split($w['m'] ?? '') as $ch) {
                        $found[$ch]++;
                    }
                }
            }
        }
        $this->assertSame($expected, $found);
        $this->assertSame(array_keys($expected), array_values(array_intersect(array_keys($expected), array_keys(config('waqf.marks')))));
    }

    public function test_marks_are_tappable_in_the_surah_reader(): void
    {
        $this->assertSame('ۘ', Quran::waqfMark('يَسۡمَعُونَۘ'));
        $this->assertSame('ۜ', Quran::waqfMark('عِوَجَاۜ'));
        $this->assertNull(Quran::waqfMark('وَيَبۡصُۜطُ')); // small seen over sad is not a stop mark

        $html = Quran::waqfHtml(app(Quran::class)->ayah(6, 36));
        $this->assertStringContainsString('class="waqf-word"', $html);

        $this->actingAs(User::factory()->create())->get('/quran/6')
            ->assertOk()->assertSee('waqf-word', false)->assertSee('علامات الوقف');
    }
}
