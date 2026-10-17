<?php

namespace App\Http\Controllers;

use App\Models\ActivityDay;
use App\Models\MemorizedAyah;
use App\Services\Quran;
use Illuminate\Http\Request;

class HifzController extends Controller
{
    public function index(Request $request, Quran $quran)
    {
        $user = $request->user();
        $counts = $user->memorized()->selectRaw('surah, count(*) as c')->groupBy('surah')->pluck('c', 'surah');
        $surahs = collect($quran->index())->map(fn ($s) => $s + ['done' => (int) ($counts[$s['n']] ?? 0)]);

        $inProgress = $surahs->filter(fn ($s) => $s['done'] > 0 && $s['done'] < $s['count'])->values();

        // Suggested surah: one in progress, else the next short surah from the end of the mushaf.
        $suggested = $inProgress->first()
            ?? $surahs->reverse()->first(fn ($s) => $s['done'] === 0 && $s['n'] >= 78)
            ?? $surahs->first(fn ($s) => $s['done'] < $s['count']);

        return view('hifz.index', [
            'surahs' => $surahs,
            'juzAmma' => $surahs->filter(fn ($s) => $s['n'] >= 78)->reverse()->values(),
            'inProgress' => $inProgress,
            'suggested' => $suggested,
            'total' => (int) $counts->sum(),
            'dueReview' => $user->memorized()->whereDate('next_review_on', '<=', now()->toDateString())->count(),
            'today' => (int) $user->today()->ayahs_memorized,
            'target' => (int) $user->pref('hifz_daily_target'),
            'streak' => ActivityDay::streak($user),
        ]);
    }

    public function learn(Request $request, Quran $quran, int $surah)
    {
        $s = $quran->surah($surah) ?? abort(404);
        $user = $request->user();
        $memorized = $user->memorized()->where('surah', $surah)->pluck('ayah')->map(fn ($a) => (int) $a)->all();

        $from = (int) $request->query('from', 0);
        if ($from < 1 || $from > $s['count']) {
            $from = 1;
            while (in_array($from, $memorized, true) && $from < $s['count']) {
                $from++;
            }
        }

        return view('hifz.learn', [
            'surah' => $s,
            'from' => $from,
            'memorized' => $memorized,
            'target' => max(1, (int) $user->pref('hifz_daily_target')),
            'reciters' => Quran::RECITERS,
            'reciter' => Quran::reciter($user->pref('reciter')),
        ]);
    }

    public function review(Request $request, Quran $quran)
    {
        $user = $request->user();
        $reciter = Quran::reciter($user->pref('reciter'));
        $due = $user->memorized()
            ->whereDate('next_review_on', '<=', now()->toDateString())
            ->orderBy('surah')->orderBy('ayah')->limit(60)->get();

        $items = $due->map(fn ($m) => [
            's' => $m->surah,
            'a' => $m->ayah,
            'name' => $quran->surah($m->surah)['name'],
            'text' => $quran->ayah($m->surah, $m->ayah),
            'audio' => $quran->audioUrl($reciter, $m->surah, $m->ayah),
            'prev' => $m->ayah > 1 ? $quran->ayah($m->surah, $m->ayah - 1) : null,
        ])->values();

        return view('hifz.review', ['items' => $items]);
    }

    public function mark(Request $request, Quran $quran)
    {
        $data = $request->validate([
            'surah' => 'required|integer|min:1|max:114',
            'ayah' => 'required|integer|min:1',
            'action' => 'required|in:memorized,good,again,forget',
        ]);
        abort_unless($quran->exists($data['surah'], $data['ayah']), 422);

        $user = $request->user();
        $row = MemorizedAyah::query()->firstOrNew(['user_id' => $user->id, 'surah' => $data['surah'], 'ayah' => $data['ayah']]);
        $today = now()->startOfDay();

        switch ($data['action']) {
            case 'memorized':
                if (! $row->exists) {
                    $row->fill(['level' => 0, 'memorized_at' => now(), 'next_review_on' => $today->copy()->addDay()]);
                    $row->save();
                    ActivityDay::bump($user, 'ayahs_memorized');
                }
                break;
            case 'good':
                if ($row->exists) {
                    $row->level = min($row->level + 1, count(MemorizedAyah::INTERVALS) - 1);
                    $row->next_review_on = $today->copy()->addDays(MemorizedAyah::intervalFor($row->level));
                    $row->last_reviewed_at = now();
                    $row->save();
                    ActivityDay::bump($user, 'ayahs_reviewed');
                }
                break;
            case 'again':
                if ($row->exists) {
                    $row->level = 0;
                    $row->next_review_on = $today->copy()->addDay();
                    $row->last_reviewed_at = now();
                    $row->save();
                    ActivityDay::bump($user, 'ayahs_reviewed');
                }
                break;
            case 'forget':
                $row->exists && $row->delete();
                break;
        }

        return response()->json([
            'memorized' => $user->memorized()->where('surah', $data['surah'])->count(),
            'today' => (int) $user->today()->ayahs_memorized,
        ]);
    }

    public function target(Request $request)
    {
        $data = $request->validate(['target' => 'required|integer|min:1|max:20']);
        $request->user()->setPrefs(['hifz_daily_target' => (int) $data['target']]);

        return back();
    }
}
