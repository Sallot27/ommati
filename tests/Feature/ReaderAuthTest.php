<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_are_asked_for_their_name(): void
    {
        $this->get('/')->assertRedirect('/hello');
        $this->get('/hifz')->assertRedirect('/hello');
        $this->get('/hello')->assertOk()->assertSee('ما اسمك؟');
        $this->get('/manifest.webmanifest')->assertOk();
        $this->get('/offline')->assertOk();
    }

    public function test_signing_in_with_the_same_name_returns_the_same_account(): void
    {
        $this->post('/hello', ['name' => '  أم   محمد '])->assertRedirect('/');
        $this->assertAuthenticated();
        $id = auth()->id();
        $this->assertSame('أم محمد', auth()->user()->name);

        $this->post('/bye')->assertRedirect('/hello');
        $this->assertGuest();

        $this->post('/hello', ['name' => 'أم محمد'])->assertRedirect('/');
        $this->assertSame($id, auth()->id());
        $this->assertSame(1, User::where('is_admin', false)->count());
    }

    public function test_name_cannot_sign_into_admin_account(): void
    {
        User::factory()->create(['name' => 'admin', 'is_admin' => true]);
        $this->post('/hello', ['name' => 'admin']);
        $this->assertFalse(auth()->user()->is_admin);
    }

    public function test_preferences_are_per_person(): void
    {
        $this->actingAs($a = User::readerNamed('أم محمد'));
        $this->post('/settings', ['reciter' => 'ar.alafasy', 'hifz_daily_target' => 5]);
        $this->assertSame('ar.alafasy', $a->fresh()->pref('reciter'));
        $this->assertSame('ar.husary', User::readerNamed('سارة')->pref('reciter'));
    }
}
