<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\Reminder;
use App\Services\Telegram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\PushSubscription;
use Throwable;

class SettingsController extends Controller
{
    private const KEYS = ['app_title', 'reminder_morning', 'reminder_evening', 'reminder_hifz', 'reminder_daily', 'reminder_sleep'];

    public function edit(Telegram $telegram)
    {
        $webhook = null;
        if ($telegram->enabled()) {
            try {
                $webhook = $telegram->call('getWebhookInfo');
            } catch (Throwable $e) {
                $webhook = ['error' => $e->getMessage()];
            }
        }

        return view('admin.settings', [
            'values' => collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all()
                + ['reminders_enabled' => Setting::get('reminders_enabled'), 'prayer_reminders' => Setting::get('prayer_reminders')],
            'telegram' => $telegram->enabled(),
            'webhook' => $webhook,
            'devices' => PushSubscription::query()->count(),
        ]);
    }

    public function update(Request $request)
    {
        $time = ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'];
        $data = $request->validate([
            'app_title' => 'required|string|max:60',
            'reminder_morning' => $time,
            'reminder_evening' => $time,
            'reminder_hifz' => $time,
            'reminder_daily' => $time,
            'reminder_sleep' => $time,
        ]);
        $data['reminders_enabled'] = $request->boolean('reminders_enabled') ? '1' : '0';
        $data['prayer_reminders'] = $request->boolean('prayer_reminders') ? '1' : '0';
        Setting::putMany(array_map(fn ($v) => $v ?? '', $data));

        return back()->with('status', 'تم حفظ الإعدادات.');
    }

    public function telegramWebhook(Telegram $telegram)
    {
        abort_unless($telegram->enabled(), 422, 'أضف TELEGRAM_BOT_TOKEN أولاً.');
        abort_unless(config('services.telegram.webhook_secret'), 422, 'أضف TELEGRAM_WEBHOOK_SECRET أولاً.');

        $telegram->call('setWebhook', [
            'url' => route('telegram.webhook'),
            'secret_token' => config('services.telegram.webhook_secret'),
            'allowed_updates' => ['message', 'channel_post'],
        ]);

        return back()->with('status', 'تم ربط البوت بالتطبيق.');
    }

    public function pushTest()
    {
        Notification::send(User::query()->whereHas('pushSubscriptions')->get(), new Reminder('تجربة الإشعارات', 'هذا إشعار تجريبي من لوحة التحكم', url('/')));

        return back()->with('status', 'تم إرسال إشعار تجريبي لكل الأجهزة المشتركة.');
    }
}
