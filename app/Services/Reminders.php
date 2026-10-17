<?php

namespace App\Services;

use App\Models\MemorizedAyah;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\Reminder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Decides which reminders are due right now and sends each once per day. */
class Reminders
{
    /** Minutes after the set time during which a missed reminder is still sent. */
    private const WINDOW = 10;

    public function __construct(private Daily $daily, private Quran $quran, private PrayerTimes $prayer) {}

    /** @return list<string> keys of reminders sent */
    public function run(?Carbon $now = null): array
    {
        $now ??= now();
        $sent = [];

        if (Setting::get('reminders_enabled') !== '1') {
            return $sent;
        }

        $reader = User::reader();
        if (! $reader->pushSubscriptions()->exists()) {
            return $sent;
        }

        $name = (string) Setting::get('aunt_name');

        foreach ($this->scheduled($name) as $type => [$time, $build]) {
            if ($time && $this->isDue($now, $time) && $this->claim($now->toDateString().':'.$type)) {
                [$title, $body, $url] = $build();
                $reader->notify(new Reminder($title, $body, $url, $type));
                $sent[] = $type;
            }
        }

        if (Setting::get('prayer_reminders') === '1') {
            try {
                $times = $this->prayer->forDate($now);
            } catch (Throwable) {
                $times = null;
            }
            if ($times) {
                $local = $now->copy()->setTimezone($times['timezone']);
                foreach (['Fajr', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'] as $p) {
                    $at = $times['timings'][$p] ?? null;
                    if ($at && $this->isDue($local, $at, 5) && $this->claim($local->toDateString().':prayer:'.$p)) {
                        $ar = PrayerTimes::NAMES[$p];
                        $reader->notify(new Reminder("حان وقت صلاة {$ar}", "الصلاة خير من الدنيا وما فيها — صلاة {$ar} الساعة {$at}", url('/prayer'), 'prayer'));
                        $sent[] = 'prayer:'.$p;
                    }
                }
            }
        }

        DB::table('sent_reminders')->where('created_at', '<', now()->subDays(3))->delete();

        return $sent;
    }

    private function scheduled(string $name): array
    {
        return [
            'morning' => [Setting::get('reminder_morning'), fn () => ['أذكار الصباح ☀️', "صباحك طمأنينة يا {$name}، حان وقت أذكار الصباح", url('/adhkar/morning')]],
            'daily' => [Setting::get('reminder_daily'), function () {
                [$s, $a] = $this->daily->ayahRef();
                $text = mb_substr($this->quran->ayah($s, $a), 0, 90);

                return ['آية اليوم وحديثه 📖', "﴿{$text}…﴾", url('/ayah')];
            }],
            'hifz' => [Setting::get('reminder_hifz'), function () {
                $due = MemorizedAyah::query()->whereDate('next_review_on', '<=', now()->toDateString())->count();
                $target = (int) Setting::get('hifz_daily_target');
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
