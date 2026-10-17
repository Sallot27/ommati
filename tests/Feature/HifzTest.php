<?php

namespace Tests\Feature;

use App\Models\MemorizedAyah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HifzTest extends TestCase
{
    use RefreshDatabase;

    public function test_memorize_then_review_with_spaced_repetition(): void
    {
        $this->withoutVite();
        $this->postJson('/hifz/mark', ['surah' => 112, 'ayah' => 1, 'action' => 'memorized'])->assertOk()->assertJson(['memorized' => 1, 'today' => 1]);
        $row = MemorizedAyah::first();
        $this->assertSame(now()->addDay()->toDateString(), $row->next_review_on->toDateString());

        // Not due today
        $this->get('/hifz/review')->assertSee('لا توجد مراجعة اليوم');

        $this->travel(1)->days();
        $this->get('/hifz/review')->assertSee('سورة');
        $this->postJson('/hifz/mark', ['surah' => 112, 'ayah' => 1, 'action' => 'good'])->assertOk();
        $row->refresh();
        $this->assertSame(1, $row->level);
        $this->assertSame(now()->addDays(MemorizedAyah::INTERVALS[1])->toDateString(), $row->next_review_on->toDateString());

        $this->postJson('/hifz/mark', ['surah' => 112, 'ayah' => 1, 'action' => 'again'])->assertOk();
        $this->assertSame(0, $row->refresh()->level);
    }

    public function test_learn_starts_at_first_unmemorized_ayah(): void
    {
        $this->withoutVite();
        MemorizedAyah::create(['surah' => 112, 'ayah' => 1]);
        MemorizedAyah::create(['surah' => 112, 'ayah' => 2]);
        $this->get('/hifz/learn/112')->assertOk()->assertViewHas('from', 3);
    }

    public function test_invalid_ayah_is_rejected(): void
    {
        $this->postJson('/hifz/mark', ['surah' => 112, 'ayah' => 9, 'action' => 'memorized'])->assertStatus(422);
    }
}
