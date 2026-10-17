<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\Reminder;
use App\Services\Reminders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_subscription_and_due_reminders_are_sent_once(): void
    {
        Notification::fake();
        Http::fake(['api.aladhan.com/*' => Http::response(['data' => [
            'timings' => ['Fajr' => '04:40', 'Sunrise' => '05:58', 'Dhuhr' => '11:40', 'Asr' => '15:00', 'Maghrib' => '17:22', 'Isha' => '18:52'],
            'date' => ['hijri' => null], 'meta' => ['timezone' => 'Asia/Riyadh'],
        ]])]);

        $this->actingAs($user = User::readerNamed('أم محمد'));
        $this->postJson('/push/subscribe', ['endpoint' => 'https://push.example/abc', 'keys' => ['p256dh' => 'k', 'auth' => 'a']])->assertOk();
        $this->assertSame(1, $user->pushSubscriptions()->count());

        Setting::put('reminder_morning', '06:30');
        $at = Carbon::parse('2026-10-17 06:32', 'Asia/Riyadh');
        $this->travelTo($at);

        $sent = app(Reminders::class)->run($at);
        $this->assertContains($user->id.':morning', $sent);
        Notification::assertSentTo($user, Reminder::class, fn ($n) => str_contains($n->url, '/adhkar/morning') && str_contains($n->body, 'أم محمد'));

        // Not sent twice
        $this->assertNotContains($user->id.':morning', app(Reminders::class)->run($at->copy()->addMinute()));

        // Prayer reminder at Dhuhr
        $dhuhr = Carbon::parse('2026-10-17 11:41', 'Asia/Riyadh');
        $this->travelTo($dhuhr);
        $this->assertContains($user->id.':prayer:Dhuhr', app(Reminders::class)->run($dhuhr));
    }

    public function test_nothing_sent_without_subscribers(): void
    {
        Notification::fake();
        $this->assertSame([], app(Reminders::class)->run(Carbon::parse('2026-10-17 06:30', 'Asia/Riyadh')));
        Notification::assertNothingSent();
    }
}
