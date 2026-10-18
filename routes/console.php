<?php

use App\Models\HadithPool;
use App\Models\User;
use App\Services\Daily;
use App\Services\Hadeeth;
use App\Services\Quran;
use App\Services\Reminders;
use App\Services\Tafsir;
use App\Services\Telegram;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;

Artisan::command('app:setup', function (Hadeeth $hadeeth) {
    $email = config('app.admin_email');
    $password = config('app.admin_password');
    if ($email && $password) {
        $admin = User::query()->firstOrNew(['email' => $email]);
        $admin->fill(['name' => 'admin', 'is_admin' => true]);
        if (! $admin->exists || ! Hash::check($password, $admin->password)) {
            $admin->password = $password;
        }
        $admin->save();
        $this->info("Admin ready: {$email}");
    } else {
        $this->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set — admin account not created.');
    }

    if (! HadithPool::query()->exists()) {
        $this->info('Hadith pool: '.$hadeeth->syncPool().' entries');
    }
})->purpose('Create the admin account and prepare data (safe to run on every deploy)');

Artisan::command('hadith:sync', function (Hadeeth $hadeeth) {
    $this->info('Synced '.$hadeeth->syncPool().' hadith ids.');
})->purpose('Refresh the list of hadiths used for the daily hadith');

Artisan::command('daily:prepare', function (Daily $daily, Tafsir $tafsir, Quran $quran) {
    $hadith = $daily->hadith();
    [$s, $from, $to] = $daily->ayahRef();
    foreach (['muyassar', 'saadi', 'tadabbur'] as $source) {
        $tafsir->range($source, $s, $from, $to);
    }
    $this->info("Ayah {$s}:{$from}-{$to}, hadith ".($hadith?->id ?? 'none'));
})->purpose("Pre-fetch today's ayah tafsir and hadith");

Artisan::command('reminders:send', function (Reminders $reminders) {
    $sent = $reminders->run();
    if ($sent) {
        $this->info('Sent: '.implode(', ', $sent));
    }
})->purpose('Send any reminders that are due now');

Artisan::command('telegram:webhook', function (Telegram $telegram) {
    if (! $telegram->enabled()) {
        $this->error('Set TELEGRAM_BOT_TOKEN first.');

        return 1;
    }
    $telegram->setWebhook(route('telegram.webhook'));
    $this->info('Webhook set to '.route('telegram.webhook'));
})->purpose('Point the Telegram bot at this app');

Schedule::command('reminders:send')->everyMinute()->withoutOverlapping();
Schedule::command('daily:prepare')->dailyAt('00:05');
Schedule::command('hadith:sync')->weekly();
