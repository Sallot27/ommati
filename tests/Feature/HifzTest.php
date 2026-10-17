<?php

namespace Tests\Feature;

use App\Models\MemorizedAyah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HifzTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_memorize_then_review_with_spaced_repetition(): void
    {
        $this->actingAs($user = User::readerNamed('أم محمد'));
        $this->postJson('/hifz/mark', ['surah' => 112, 'ayah' => 1, 'action' => 'memorized'])->assertOk()->assertJson(['memorized' => 1, 'today' => 1]);
        $row = MemorizedAyah::first();
        $this->assertSame($user->id, $row->user_id);
        $this->assertSame(now()->addDay()->toDateString(), $row->next_review_on->toDateString());

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

    public function test_each_person_has_their_own_progress(): void
    {
        $this->actingAs(User::readerNamed('أم محمد'));
        $this->postJson('/hifz/mark', ['surah' => 112, 'ayah' => 1, 'action' => 'memorized'])->assertOk();
        $this->postJson('/hifz/mark', ['surah' => 112, 'ayah' => 2, 'action' => 'memorized'])->assertOk();
        $this->get('/hifz/learn/112')->assertOk()->assertViewHas('from', 3);

        $this->actingAs(User::readerNamed('سارة'));
        $this->get('/hifz/learn/112')->assertOk()->assertViewHas('from', 1);
        $this->get('/hifz')->assertViewHas('total', 0);
    }

    public function test_invalid_ayah_is_rejected(): void
    {
        $this->actingAs(User::readerNamed('أم محمد'));
        $this->postJson('/hifz/mark', ['surah' => 112, 'ayah' => 9, 'action' => 'memorized'])->assertStatus(422);
    }
}
