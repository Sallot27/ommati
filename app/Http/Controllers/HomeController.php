<?php

namespace App\Http\Controllers;

use App\Models\ActivityDay;
use App\Models\Book;
use App\Models\DailyPick;
use App\Models\Hadith;
use App\Models\MemorizedAyah;
use App\Models\Message;
use App\Models\Setting;
use App\Services\Daily;
use App\Services\Quran;

class HomeController extends Controller
{
    public function index(Daily $daily, Quran $quran)
    {
        $hour = (int) now()->format('G');
        $greeting = match (true) {
            $hour < 4 => 'طابت ليلتك',
            $hour < 12 => 'صباح الخير',
            $hour < 17 => 'نهارك سعيد',
            default => 'مساء الخير',
        };

        // Morning adhkar until mid-afternoon, evening adhkar after.
        $adhkarSlug = ($hour >= 4 && $hour < 15) ? 'morning' : 'evening';

        [$s, $from, $to] = $daily->ayahRef();
        $ayahText = $quran->ayah($s, $from);

        // Only show the hadith if it's already fetched (never block the home page on the network).
        $pick = DailyPick::query()->find(now()->toDateString());
        $hadith = $pick?->hadith_id ? Hadith::query()->find($pick->hadith_id) : null;

        $lastRead = Setting::get('last_read');
        $lastRead = $lastRead ? json_decode($lastRead, true) : null;

        return view('home', [
            'greeting' => $greeting,
            'auntName' => Setting::get('aunt_name'),
            'adhkarSlug' => $adhkarSlug,
            'ayah' => ['surah' => $quran->surah($s)['name'], 's' => $s, 'from' => $from, 'to' => $to, 'text' => $ayahText],
            'hadith' => $hadith,
            'message' => Message::query()->where('is_active', true)->latest()->first(),
            'memorized' => MemorizedAyah::query()->count(),
            'dueReview' => MemorizedAyah::query()->whereDate('next_review_on', '<=', now()->toDateString())->count(),
            'streak' => ActivityDay::streak(),
            'lastRead' => $lastRead && $quran->exists((int) $lastRead['s'], (int) $lastRead['a'])
                ? ['s' => (int) $lastRead['s'], 'a' => (int) $lastRead['a'], 'name' => $quran->surah((int) $lastRead['s'])['name']]
                : null,
            'latestBook' => Book::query()->latest()->first(),
        ]);
    }
}
