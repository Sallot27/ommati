<?php

namespace App\Services;

use App\Models\User;

/** A reading plan that completes the mushaf (604 pages) in a chosen number of days. */
class Khatma
{
    public const PAGES = 604;

    public const PLANS = [30 => 'شهر', 60 => 'شهرين', 90 => 'ثلاثة أشهر', 15 => 'أسبوعين', 10 => 'عشرة أيام', 7 => 'أسبوع'];

    public function __construct(private Quran $quran) {}

    /** @return array{days: int, page: int, started: string, completed: int}|null */
    public function plan(User $user): ?array
    {
        $p = $user->pref('khatma');

        return is_array($p) && isset($p['days']) ? $p : null;
    }

    public function start(User $user, int $days): void
    {
        $user->setPrefs(['khatma' => ['days' => $days, 'page' => 0, 'started' => now()->toDateString(), 'completed' => (int) ($this->plan($user)['completed'] ?? 0)]]);
    }

    public function stop(User $user): void
    {
        $user->setPrefs(['khatma' => null]);
    }

    public function perDay(array $plan): int
    {
        return (int) ceil(self::PAGES / max(1, $plan['days']));
    }

    /** @return array{from: int, to: int} pages of today's portion */
    public function portion(array $plan): array
    {
        $from = min(self::PAGES, $plan['page'] + 1);

        return ['from' => $from, 'to' => min(self::PAGES, $plan['page'] + $this->perDay($plan))];
    }

    /** Mark today's portion read. Returns true when the mushaf was completed. */
    public function advance(User $user): bool
    {
        $plan = $this->plan($user);
        if (! $plan) {
            return false;
        }
        $plan['page'] = $this->portion($plan)['to'];
        $done = $plan['page'] >= self::PAGES;
        if ($done) {
            $plan['completed'] = ($plan['completed'] ?? 0) + 1;
            $plan['page'] = 0;
            $plan['started'] = now()->toDateString();
        }
        $user->setPrefs(['khatma' => $plan]);

        return $done;
    }

    /**
     * Ayat on the given pages, grouped by surah.
     *
     * @return list<array{surah: array, ayahs: list<array{n: int, text: string, page: int}>}>
     */
    public function pages(int $from, int $to): array
    {
        $groups = [];
        foreach ($this->quran->all() as $s) {
            if ($s['pages'][count($s['pages']) - 1] < $from || $s['pages'][0] > $to) {
                continue;
            }
            $ayahs = [];
            foreach ($s['ayahs'] as $i => $text) {
                $p = $s['pages'][$i];
                if ($p >= $from && $p <= $to) {
                    $ayahs[] = ['n' => $i + 1, 'text' => $text, 'page' => $p];
                }
            }
            if ($ayahs) {
                $groups[] = ['surah' => $s, 'ayahs' => $ayahs];
            }
        }

        return $groups;
    }
}
