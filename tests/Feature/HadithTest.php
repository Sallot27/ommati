<?php

namespace Tests\Feature;

use App\Models\Hadith;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HadithTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_hadith_is_fetched_with_explanation_and_benefits(): void
    {
        $this->withoutVite();
        Http::preventStrayRequests();
        Http::fake([
            'hadeethenc.com/api/v1/hadeeths/list/*' => Http::response(['data' => [['id' => '2962', 'title' => 'عنوان']], 'meta' => ['last_page' => 1]]),
            'hadeethenc.com/api/v1/hadeeths/one/*' => Http::response([
                'id' => '2962', 'title' => 'حديث تجريبي', 'hadeeth' => 'نص الحديث', 'attribution' => 'متفق عليه', 'grade' => 'صحيح',
                'explanation' => 'شرح الحديث', 'hints' => ['فائدة أولى', 'فائدة ثانية'], 'words_meanings' => [['word' => 'كلمة', 'meaning' => 'معنى']], 'reference' => 'صحيح البخاري',
            ]),
        ]);

        $this->get('/hadith')->assertOk()->assertSee('نص الحديث')->assertSee('فائدة أولى')->assertSee('شرح الحديث');
        $this->assertSame(1, Hadith::count());

        // Saved hadith toggles.
        $this->postJson('/hadith/2962/save')->assertJson(['saved' => true]);
        $this->get('/hadith/saved')->assertSee('حديث تجريبي');

        // Second visit uses the cached copy.
        Http::fake(fn () => throw new \RuntimeException('should not call'));
        $this->get('/hadith')->assertOk()->assertSee('نص الحديث');
    }

    public function test_hadith_page_handles_network_failure(): void
    {
        $this->withoutVite();
        Http::fake(['*' => Http::response('', 500)]);
        $this->get('/hadith')->assertOk()->assertSee('تعذّر تحميل حديث اليوم');
    }
}
