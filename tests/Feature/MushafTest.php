<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Mushaf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MushafTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_maps_every_ayah_to_a_page(): void
    {
        $m = app(Mushaf::class);
        $this->assertSame(6236, array_sum(array_map('count', $m->ayahPages())));
        $this->assertSame(1, $m->pageOf(1, 1));
        $this->assertSame(282, $m->pageOf(17, 1));
        $this->assertSame(604, $m->pageOf(114, 6));
        $this->assertCount(30, $m->juzStarts());
        $this->assertFileExists(public_path('mushaf-data/pages/604.json'));
    }

    public function test_opens_on_an_ayah_and_remembers_the_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/mushaf?aya=17_1')->assertOk()->assertViewHas('cfg', fn ($c) => $c['page'] === 282 && $c['focus'] === '17:1');
        $this->actingAs($user)->get('/mushaf?s=2&a=255')->assertOk()->assertViewHas('cfg', fn ($c) => $c['page'] === 42 && $c['focus'] === '2:255');

        $this->actingAs($user)->postJson('/mushaf-page', ['page' => 300])->assertNoContent();
        $this->assertSame(300, $user->fresh()->pref('mushaf_page'));
        $this->actingAs($user)->get('/mushaf')->assertOk()->assertViewHas('cfg', fn ($c) => $c['page'] === 300);

        $this->actingAs($user)->get('/mushaf/605')->assertNotFound();
        $this->actingAs($user)->postJson('/mushaf-page', ['page' => 0])->assertStatus(422);
    }
}
