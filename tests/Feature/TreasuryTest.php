<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Hijri;
use App\Services\Khatma;
use App\Services\Occasions;
use App\Services\Quran;
use App\Services\Treasury;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TreasuryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Http::fake([
            '*tafsir_api*' => Http::response(['text' => 'نص التفسير']),
            'hadeethenc.com/api/v1/hadeeths/one/*' => Http::response([
                'id' => '6183', 'title' => 'عنوان', 'hadeeth' => 'نص الحديث التجريبي', 'attribution' => 'رواه الترمذي', 'grade' => 'صحيح',
                'explanation' => 'شرح', 'hints' => ['فائدة'], 'words_meanings' => [], 'reference' => 'مرجع',
            ]),
        ]);
        $this->actingAs(User::readerNamed('أم محمد'));
    }

    public function test_new_sections_render(): void
    {
        foreach (['/names', '/names/0', '/names/82', '/names/90', '/names/98', '/duas', '/quotes', '/quotes?who='.urlencode('ابن تيمية'), '/quotes?page=2', '/days', '/khatma', '/more', '/'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/names/500')->assertNotFound();
        $this->get('/quotes?who=nobody')->assertNotFound();
    }

    public function test_every_name_appears_in_its_ayah(): void
    {
        $quran = app(Quran::class);
        $names = app(Treasury::class)->names();
        $this->assertCount(99, $names);
        foreach ($names as $n) {
            $this->assertTrue(isset($n['surah']) ? $quran->exists($n['surah'], $n['ayah']) : (isset($n['hadith']) || isset($n['dorar'])), $n['name']);
        }
    }

    public function test_quotes_are_only_from_the_listed_imams(): void
    {
        $data = app(Treasury::class)->quotesData();
        foreach ($data['quotes'] as $q) {
            $this->assertArrayHasKey($q['who'], $data['imams']);
            $this->assertNotSame('', $q['ref']);
        }
        $this->assertArrayHasKey(app(Treasury::class)->quoteOfDay()['who'], $data['imams']);
    }

    public function test_duas_reference_real_ayat(): void
    {
        $quran = app(Quran::class);
        foreach (config('quran_duas') as [$ref]) {
            [$s, $from, $to] = Quran::parseRef($ref);
            $this->assertTrue($quran->exists($s, $from) && $quran->exists($s, $to), $ref);
        }
    }

    public function test_khatma_plan_advances_and_completes(): void
    {
        $this->post('/khatma/start', ['days' => 30])->assertRedirect('/khatma/read');
        $this->get('/khatma/read')->assertOk()->assertSee('سورة الفاتحة');
        $this->post('/khatma/done')->assertRedirect('/khatma');
        $user = auth()->user()->fresh();
        $this->assertSame(21, $user->pref('khatma')['page']);
        $this->assertTrue($user->today()->khatma_read);
        $this->get('/')->assertSee('ورد الختمة');

        $k = app(Khatma::class);
        $user->setPrefs(['khatma' => ['days' => 30, 'page' => 600, 'started' => now()->toDateString(), 'completed' => 0]]);
        $this->assertTrue($k->advance($user));
        $this->assertSame(1, $user->fresh()->pref('khatma')['completed']);
        $this->assertSame(0, $user->fresh()->pref('khatma')['page']);
    }

    public function test_virtuous_days_follow_the_hijri_calendar(): void
    {
        $occ = app(Occasions::class);

        // Find the 9th of Dhu al-Hijjah within the coming year.
        $d = Carbon::parse('2026-10-18', 'Asia/Riyadh');
        for ($i = 0; $i < 400; $i++, $d->addDay()) {
            $h = Hijri::parts($d);
            if ($h['month'] === 12 && $h['day'] === 9) {
                break;
            }
        }
        $this->assertContains('arafah', array_column($occ->on($d), 'key'));
        $this->assertSame('غداً يوم عرفة', $occ->fastingTomorrow($d->copy()->subDay()));

        // A Monday that isn't a white day or in Ramadan.
        $monday = Carbon::parse('2026-10-19', 'Asia/Riyadh');
        $this->assertContains('monthu', array_column($occ->on($monday), 'key'));
        $this->assertSame('غداً الاثنين', $occ->fastingTomorrow($monday->copy()->subDay()));
    }
}
