<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Quran;
use App\Services\Tafsir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::readerNamed('أم محمد'));
        Http::preventStrayRequests();
        Http::fake([
            '*tafsir_api*tadabbur-wa-amal*' => Http::response(['text' => "الوقفات التدبرية\n\n١- ﴿فَإِنَّ مَعَ ٱلْعُسْرِ يُسْرًا﴾\n\nبشارة عظيمة. [السعدي: ١]\n\nالسؤال: ما البشارة؟\n\nالتوجيهات\n\n١- لا تيأس، ﴿فَإِنَّ مَعَ ٱلْعُسْرِ يُسْرًا﴾\n\nالعمل بالآيات\n\n١- اذكر نعمة أنعم الله بها عليك، ﴿وَأَمَّا بِنِعْمَةِ رَبِّكَ فَحَدِّثْ﴾\n٢- تفاءل عند الشدة، ﴿فَإِنَّ مَعَ ٱلْعُسْرِ يُسْرًا﴾\n\nمعاني الكلمات\n\n﴿يُسْرًا﴾ سهولة."]),
            '*tafsir_api*' => Http::response(['text' => 'نص التفسير التجريبي']),
            'api.aladhan.com/*' => Http::response(['data' => [
                'timings' => ['Fajr' => '04:40', 'Sunrise' => '05:58', 'Dhuhr' => '11:40', 'Asr' => '15:00', 'Maghrib' => '17:22', 'Isha' => '18:52'],
                'date' => ['hijri' => ['day' => '6', 'month' => ['ar' => 'جمادى الأولى'], 'year' => '1448']],
                'meta' => ['timezone' => 'Asia/Riyadh'],
            ]]),
        ]);
    }

    public function test_all_public_pages_render(): void
    {
        foreach (['/', '/quran', '/quran/1', '/quran/114', '/listen', '/adhkar', '/adhkar/morning', '/adhkar/evening', '/hifz', '/hifz/learn/112', '/hifz/review', '/library', '/prayer', '/tasbih', '/more', '/settings', '/install', '/offline', '/hadith/saved', '/manifest.webmanifest'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_home_greets_the_reader_by_name(): void
    {
        $this->get('/')->assertOk()->assertSee('يا أم محمد')->assertSee('وِرد اليوم');
    }

    public function test_unknown_pages_404(): void
    {
        $this->get('/quran/115')->assertNotFound();
        $this->get('/adhkar/nope')->assertNotFound();
        $this->get('/api/tafsir/1/8')->assertNotFound();
    }

    public function test_quran_api_returns_text_and_audio(): void
    {
        $res = $this->getJson('/api/quran/1')->assertOk();
        $this->assertCount(7, $res->json('ayahs'));
        $this->assertStringContainsString('cdn.islamic.network', $res->json('ayahs.0.audio'));
        // Basmala is not prefixed to ayah 1 of other surahs.
        $this->assertStringStartsWith('الٓمٓ', $this->getJson('/api/quran/2')->json('ayahs.0.text'));
    }

    public function test_whole_surah_reciters(): void
    {
        $res = $this->getJson('/api/quran/1?reciter=mp3.adel-rayyan')->assertOk();
        $this->assertSame('https://cdn.mp3quran.net/audio/adel-rayyan/r1/001.mp3', $res->json('full'));
        $this->assertStringContainsString('ar.husary', $res->json('ayahs.0.audio')); // per-ayah falls back

        // Dibirov didn't record al-Fatiha, but did record al-Baqarah.
        $this->assertNull($this->getJson('/api/quran/1?reciter=mp3.muhammad-dibirov')->json('full'));
        $this->assertStringEndsWith('/002.mp3', $this->getJson('/api/quran/2?reciter=mp3.muhammad-dibirov')->json('full'));
        $this->assertNull($this->getJson('/api/quran/37?reciter=mp3.islam-sobhi')->json('full'));

        $this->post('/settings', ['reciter' => 'mp3.islam-sobhi'])->assertRedirect();
        $this->get('/hifz/learn/112')->assertOk()->assertViewHas('reciter', 'ar.husary');
        $this->get('/listen')->assertOk()->assertSee('عادل ريان')->assertSee('إسلام صبحي')->assertSee('محمد ديبيروف');
    }

    public function test_daily_ayah_shows_tafsir_and_actions(): void
    {
        $this->get('/ayah')->assertOk()->assertSee('كيف أعمل بها')->assertSee('نص التفسير التجريبي');
    }

    public function test_tadabbur_puts_actions_for_this_ayah_first(): void
    {
        $t = app(Tafsir::class)->tadabbur(94, 5, 'فَإِنَّ مَعَ ٱلْعُسْرِ يُسْرًا');
        $this->assertStringStartsWith('تفاءل عند الشدة', $t['actions'][0]);
        $this->assertCount(1, $t['reflections']);
        $this->assertSame('ما البشارة؟', $t['reflections'][0]['question']);
    }

    public function test_prayer_times_api(): void
    {
        $this->getJson('/api/prayer?lat=24.7&lng=46.6')->assertOk()->assertJsonPath('today.timings.Fajr', '04:40');
    }

    public function test_daily_ayah_list_is_valid(): void
    {
        $quran = app(Quran::class);
        foreach (config('daily_ayahs') as $ref) {
            [$s, $from, $to] = Quran::parseRef($ref);
            $this->assertTrue($quran->exists($s, $from) && $quran->exists($s, $to) && $from <= $to, $ref);
        }
    }
}
