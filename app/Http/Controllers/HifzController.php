<?php

namespace App\Http\Controllers;

use App\Models\ActivityDay;
use App\Models\MemorizedAyah;
use App\Models\Setting;
use App\Services\Quran;
use Illuminate\Http\Request;

class HifzController extends Controller
{
    public function index(Quran $quran)
    {
        $counts = MemorizedAyah::query()->selectRaw('surah, count(*) as c')->groupBy('surah')->pluck('c', 'surah');
        $surahs = collect($quran->index())->map(fn ($s) => $s + ['done' => (int) ($counts[$s['n']] ?? 0)]);

        $inProgress = $surahs->filter(fn ($s) => $s['done'] > 0 && $s['done'] < $s['count'])->values();
        $completed = $surahs->filter(fn ($s) => $s['done'] >= $s['count'])->values();

        // Suggested surah: one in progress, else the next short surah from the end of the mushaf.
        $suggested = $inProgress->first()
            ?? $surahs->reverse()->first(fn ($s) => $s['done'] === 0 && $s['n'] >= 78)
            ?? $surahs->first(fn ($s) => $s['done'] < $s['count']);

        return view('hifz.index', [
            'surahs' => $surahs,
            'juzAmma' => $surahs->filter(fn ($s) => $s['n'] >= 78)->reverse()->values(),
            'inProgress' => $inProgress,
            'completed' => $completed,
            'suggested' => $suggested,
            'total' => $counts->sum(),
            'dueReview' => MemorizedAyah::query()->whereDate('next_review_on', '<=', now()->toDateString())->count(),
            'today' => (int) (ActivityDay::query()->find(now()->toDateString())?->ayahs_memorized ?? 0),
            'target' => (int) Setting::get('hifz_daily_target'),
            'streak' => ActivityDay::streak(),
        ]);
    }

    public function learn(Request $request, Quran $quran, int $surah)
    {
        $s = $quran->surah($surah) ?? abort(404);
        $memorized = MemorizedAyah::query()->where('surah', $surah)->pluck('ayah')->all();

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
            'target' => max(1, (int) Setting::get('hifz_daily_target')),
            'reciters' => Quran::RECITERS,
            'reciter' => Quran::reciter(Setting::get('reciter')),
        ]);
    }

    public function review(Quran $quran)
    {
        $reciter = Quran::reciter(Setting::get('reciter'));
        $due = MemorizedAyah::query()
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

        $row = MemorizedAyah::query()->firstOrNew(['surah' => $data['surah'], 'ayah' => $data['ayah']]);
        $today = now()->startOfDay();

        switch ($data['action']) {
            case 'memorized':
                if (! $row->exists) {
                    $row->fill(['level' => 0, 'memorized_at' => now(), 'next_review_on' => $today->copy()->addDay()]);
                    $row->save();
                    ActivityDay::bump('ayahs_memorized');
                }
                break;
            case 'good':
                if ($row->exists) {
                    $row->level = min($row->level + 1, count(MemorizedAyah::INTERVALS) - 1);
                    $row->next_review_on = $today->copy()->addDays(MemorizedAyah::intervalFor($row->level));
                    $row->last_reviewed_at = now();
                    $row->save();
                    ActivityDay::bump('ayahs_reviewed');
                }
                break;
            case 'again':
                if ($row->exists) {
                    $row->level = 0;
                    $row->next_review_on = $today->copy()->addDay();
                    $row->last_reviewed_at = now();
                    $row->save();
                    ActivityDay::bump('ayahs_reviewed');
                }
                break;
            case 'forget':
                $row->exists && $row->delete();
                break;
        }

        return response()->json([
            'memorized' => MemorizedAyah::query()->where('surah', $data['surah'])->count(),
            'today' => (int) (ActivityDay::query()->find(now()->toDateString())?->ayahs_memorized ?? 0),
        ]);
    }

    public function target(Request $request)
    {
        $data = $request->validate(['target' => 'required|integer|min:1|max:20']);
        Setting::put('hifz_daily_target', (string) $data['target']);

        return back();
    }
}
