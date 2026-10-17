<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\Reminder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Decides which reminders are due right now and sends each once per day, per person. */
class Reminders
{
    /** Minutes after the set time during which a missed reminder is still sent. */
    private const WINDOW = 10;

    public function __construct(private Daily $daily, private Quran $quran, private PrayerTimes $prayer) {}

    /** @return list<string> keys of reminders sent, as "userId:type" */
    public function run(?Carbon $now = null): array
    {
        $now ??= now();
        $sent = [];

        if (Setting::get('reminders_enabled') !== '1') {
            return $sent;
        }

        $users = User::query()->whereHas('pushSubscriptions')->get();
        $prayerOn = Setting::get('prayer_reminders') === '1';

        foreach ($users as $user) {
            foreach ($this->scheduled($user) as $type => [$time, $build]) {
                if ($time && $this->isDue($now, $time) && $this->claim($user->id.':'.$now->toDateString().':'.$type)) {
                    [$title, $body, $url] = $build();
                    $user->notify(new Reminder($title, $body, $url, $type));
                    $sent[] = $user->id.':'.$type;
                }
            }

            if (! $prayerOn) {
                continue;
            }
            try {
                $times = $this->prayer->forDate($now, (float) $user->pref('location_lat'), (float) $user->pref('location_lng'));
            } catch (Throwable) {
                $times = null;
            }
            if (! $times) {
                continue;
            }
            $local = $now->copy()->setTimezone($times['timezone']);
            foreach (['Fajr', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'] as $p) {
                $at = $times['timings'][$p] ?? null;
                if ($at && $this->isDue($local, $at, 5) && $this->claim($user->id.':'.$local->toDateString().':prayer:'.$p)) {
                    $ar = PrayerTimes::NAMES[$p];
                    $user->notify(new Reminder("حان وقت صلاة {$ar}", "صلاة {$ar} الساعة {$at} — الصلاة خير من الدنيا وما فيها", url('/prayer'), 'prayer'));
                    $sent[] = $user->id.':prayer:'.$p;
                }
            }
        }

        DB::table('sent_reminders')->where('created_at', '<', now()->subDays(3))->delete();

        return $sent;
    }

    private function scheduled(User $user): array
    {
        $name = $user->name;

        return [
            'morning' => [Setting::get('reminder_morning'), fn () => ['أذكار الصباح ☀️', "صباحك طمأنينة يا {$name}، حان وقت أذكار الصباح", url('/adhkar/morning')]],
            'daily' => [Setting::get('reminder_daily'), function () {
                [$s, $a] = $this->daily->ayahRef();
                $text = mb_substr($this->quran->ayah($s, $a), 0, 90);

                return ['آية اليوم وحديثه 📖', "﴿{$text}…﴾", url('/ayah')];
            }],
            'hifz' => [Setting::get('reminder_hifz'), function () use ($user) {
                $due = $user->memorized()->whereDate('next_review_on', '<=', now()->toDateString())->count();
                $target = (int) $user->pref('hifz_daily_target');
                $body = "وِردك اليوم {$target} آيات جديدة".($due ? " و{$due} آية للمراجعة" : '').' — بارك الله في وقتك';

                return ['وِرد الحفظ 🌿', $body, url('/hifz')];
            }],
            'evening' => [Setting::get('reminder_evening'), fn () => ['أذكار المساء 🌙', "مساؤك ذكر وسكينة يا {$name}، حان وقت أذكار المساء", url('/adhkar/evening')]],
            'sleep' => [Setting::get('reminder_sleep'), fn () => ['أذكار النوم ✨', 'نامي على ذكر الله — أذكار النوم جاهزة', url('/adhkar/sleep')]],
        ];
    }

    private function isDue(Carbon $now, string $hhmm, int $window = self::WINDOW): bool
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $hhmm, $m)) {
            return false;
        }
        $at = $now->copy()->setTime((int) $m[1], (int) $m[2]);
        $diff = $at->diffInMinutes($now, false);

        return $diff >= 0 && $diff < $window;
    }

    /** Atomically mark a reminder as sent; false if it was already sent. */
    private function claim(string $key): bool
    {
        return DB::table('sent_reminders')->insertOrIgnore(['key' => $key, 'created_at' => now(), 'updated_at' => now()]) === 1;
    }
}
