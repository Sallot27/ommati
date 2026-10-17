<?php

namespace App\Http\Controllers;

use App\Models\ActivityDay;
use App\Models\Book;
use App\Models\DailyPick;
use App\Models\Hadith;
use App\Models\Message;
use App\Services\Daily;
use App\Services\Quran;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, Daily $daily, Quran $quran)
    {
        $user = $request->user();
        $hour = (int) now()->format('G');
        $greeting = match (true) {
            $hour < 4 => 'طابت ليلتك',
            $hour < 12 => 'صباح الخير',
            $hour < 17 => 'نهارك سعيد',
            default => 'مساء الخير',
        };
        $isMorning = $hour >= 4 && $hour < 15;

        [$s, $from, $to] = $daily->ayahRef();

        // Only show the hadith title if it's already fetched (never block the home page on the network).
        $pick = DailyPick::query()->find(now()->toDateString());
        $hadith = $pick?->hadith_id ? Hadith::query()->find($pick->hadith_id) : null;

        $today = $user->today();
        $target = max(1, (int) $user->pref('hifz_daily_target'));
        $due = $user->memorized()->whereDate('next_review_on', '<=', now()->toDateString())->count();

        // Today's checklist, in the order of a day.
        $tasks = [
            ['done' => $isMorning ? $today->morning_done : $today->evening_done,
                'title' => $isMorning ? 'أذكار الصباح' : 'أذكار المساء',
                'sub' => $isMorning ? 'تحصين يومك' : 'تحصين ليلتك',
                'href' => route('adhkar.show', $isMorning ? 'morning' : 'evening'), 'icon' => $isMorning ? 'sun' : 'moon'],
            ['done' => $today->ayahs_memorized >= $target,
                'title' => 'ورد الحفظ',
                'sub' => $today->ayahs_memorized >= $target ? 'أتممتِ ورد اليوم' : 'باقي '.Quran::digits($target - min($target, $today->ayahs_memorized)).' آيات',
                'href' => route('hifz.index'), 'icon' => 'sprout'],
        ];
        if ($due > 0) {
            $tasks[] = ['done' => false, 'title' => 'المراجعة', 'sub' => Quran::digits($due).' آية حان موعدها', 'href' => route('hifz.review'), 'icon' => 'repeat'];
        }
        $tasks[] = ['done' => $today->ayah_read, 'title' => 'آية اليوم', 'sub' => 'سورة '.$quran->surah($s)['name'], 'href' => route('ayah.today'), 'icon' => 'book'];
        $tasks[] = ['done' => $today->hadith_read, 'title' => 'حديث اليوم', 'sub' => $hadith?->title ?? 'الشرح والفوائد', 'href' => route('hadith.today'), 'icon' => 'quote'];

        $lastRead = $user->pref('last_read');

        return view('home', [
            'user' => $user,
            'greeting' => $greeting,
            'tasks' => $tasks,
            'ayah' => ['surah' => $quran->surah($s)['name'], 's' => $s, 'from' => $from, 'to' => $to, 'text' => $quran->ayah($s, $from)],
            'message' => Message::query()->where('is_active', true)->latest()->first(),
            'memorized' => $user->memorized()->count(),
            'streak' => ActivityDay::streak($user),
            'lastRead' => is_array($lastRead) && $quran->exists((int) $lastRead['s'], (int) $lastRead['a'])
                ? ['s' => (int) $lastRead['s'], 'a' => (int) $lastRead['a'], 'name' => $quran->surah((int) $lastRead['s'])['name']]
                : null,
            'latestBook' => Book::query()->latest()->first(),
        ]);
    }
}
