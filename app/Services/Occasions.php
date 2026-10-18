<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Virtuous days and recommended acts for a given date (Umm al-Qura calendar).
 * Each item points to a hadith on HadeethEnc (fetched and cached at runtime) —
 * no religious text is written here.
 */
class Occasions
{
    /**
     * @return list<array{key: string, title: string, sub: string, hadith: ?int, icon: string, tone: string, link?: string}>
     */
    public function on(Carbon $date): array
    {
        $h = Hijri::parts($date);
        $tomorrow = Hijri::parts($date->copy()->addDay());
        $dow = (int) $date->dayOfWeek; // 0 = Sunday
        $items = [];

        // Today
        if ($h['month'] === 9) {
            $items[] = ['key' => 'ramadan', 'title' => 'شهر رمضان', 'sub' => 'اليوم '.Quran::digits($h['day']).' من رمضان', 'hadith' => 4196, 'icon' => 'moon', 'tone' => 'teal'];
        }
        if ($h['month'] === 12 && $h['day'] === 9) {
            $items[] = ['key' => 'arafah', 'title' => 'يوم عرفة', 'sub' => 'يُستحب صيامه لغير الحاج', 'hadith' => 65874, 'icon' => 'sun', 'tone' => 'saffron'];
        } elseif ($h['month'] === 12 && $h['day'] <= 8) {
            $items[] = ['key' => 'dhulhijjah', 'title' => 'العشر الأوائل من ذي الحجة', 'sub' => 'اليوم '.Quran::digits($h['day']).' — أكثري من العمل الصالح', 'hadith' => 6255, 'icon' => 'star', 'tone' => 'saffron'];
        }
        if ($h['month'] === 1 && in_array($h['day'], [9, 10], true)) {
            $items[] = ['key' => 'ashura', 'title' => $h['day'] === 10 ? 'يوم عاشوراء' : 'تاسوعاء', 'sub' => 'يُستحب صيام التاسع والعاشر من محرم', 'hadith' => $h['day'] === 10 ? 10121 : 6217, 'icon' => 'sun', 'tone' => 'saffron'];
        }
        if ($h['month'] === 10 && $h['day'] >= 2) {
            $items[] = ['key' => 'shawwal', 'title' => 'ست من شوال', 'sub' => 'لمن صام رمضان', 'hadith' => 65523, 'icon' => 'star', 'tone' => 'teal'];
        }
        if (in_array($h['day'], [13, 14, 15], true) && ! ($h['month'] === 12 && $h['day'] === 13)) {
            $items[] = ['key' => 'beed', 'title' => 'الأيام البيض', 'sub' => 'اليوم '.Quran::digits($h['day']).' من الشهر الهجري', 'hadith' => 10108, 'icon' => 'moon', 'tone' => 'teal'];
        }
        if (in_array($dow, [1, 4], true) && $h['month'] !== 9) {
            $items[] = ['key' => 'monthu', 'title' => $dow === 1 ? 'صيام يوم الاثنين' : 'صيام يوم الخميس', 'sub' => 'كان النبي ﷺ يتحرّى صيامهما', 'hadith' => 6183, 'icon' => 'sun', 'tone' => 'teal'];
        }
        if ($dow === 5) {
            $items[] = ['key' => 'friday', 'title' => 'يوم الجمعة', 'sub' => 'فيه ساعة إجابة', 'hadith' => 65753, 'icon' => 'star', 'tone' => 'lapis', 'link' => route('quran.show', 18)];
        }

        return $items;
    }

    /** Fasting days coming tomorrow (for the evening reminder). */
    public function fastingTomorrow(Carbon $date): ?string
    {
        $t = $date->copy()->addDay();
        $h = Hijri::parts($t);
        if ($h['month'] === 9) {
            return null;
        }

        return match (true) {
            $h['month'] === 12 && $h['day'] === 9 => 'غداً يوم عرفة',
            $h['month'] === 1 && $h['day'] === 9 => 'غداً تاسوعاء',
            $h['month'] === 1 && $h['day'] === 10 => 'غداً يوم عاشوراء',
            in_array($h['day'], [13, 14, 15], true) && ! ($h['month'] === 12 && $h['day'] === 13) => 'غداً من الأيام البيض',
            (int) $t->dayOfWeek === 1 => 'غداً الاثنين',
            (int) $t->dayOfWeek === 4 => 'غداً الخميس',
            default => null,
        };
    }

    /** Upcoming notable days in the next N days. */
    public function upcoming(Carbon $from, int $days = 40): array
    {
        $out = [];
        for ($i = 1; $i <= $days; $i++) {
            $d = $from->copy()->addDays($i);
            foreach ($this->on($d) as $item) {
                if (in_array($item['key'], ['monthu', 'friday'], true)) {
                    continue; // weekly; shown separately
                }
                if (isset($out[$item['key']]) && $item['key'] !== 'beed') {
                    continue;
                }
                $key = $item['key'] === 'beed' ? 'beed-'.Hijri::parts($d)['month'] : $item['key'];
                $out[$key] ??= $item + ['date' => $d, 'hijri' => Hijri::format($d), 'in' => $i];
            }
        }

        return array_values($out);
    }
}
