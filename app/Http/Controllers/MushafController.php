<?php

namespace App\Http\Controllers;

use App\Services\Mushaf;
use App\Services\Quran;
use Illuminate\Http\Request;

class MushafController extends Controller
{
    /** /mushaf/{page?} — also accepts ?s=17&a=1 or ?aya=17_1 (like quran.ksu.edu.sa). */
    public function show(Request $request, Mushaf $mushaf, Quran $quran, ?int $page = null)
    {
        $s = $a = null;
        if (preg_match('/^(\d+)[_:](\d+)$/', (string) $request->query('aya'), $m)) {
            [$s, $a] = [(int) $m[1], (int) $m[2]];
        } elseif ($request->filled('s')) {
            [$s, $a] = [(int) $request->query('s'), max(1, (int) $request->query('a', 1))];
        }
        $focus = null;
        if ($s !== null && $quran->exists($s, $a)) {
            $page = $mushaf->pageOf($s, $a);
            $focus = "$s:$a";
        }
        $page ??= (int) $request->user()->pref('mushaf_page') ?: 1;
        abort_unless($page >= 1 && $page <= Mushaf::PAGES, 404);

        $surahs = array_map(fn ($x) => [
            'n' => $x['n'], 'name' => $x['name'], 'count' => $x['count'],
            'page' => $mushaf->pageOf($x['n']), 'start' => $quran->surah($x['n'])['start'],
        ], $quran->index());

        return view('mushaf.show', [
            'cfg' => [
                'page' => $page,
                'focus' => $focus,
                'fontUrl' => Mushaf::fontUrl(),
                'pagesUrl' => asset('mushaf-data/pages'),
                'saveUrl' => route('mushaf.save'),
                'reciter' => Quran::reciter($request->user()->pref('reciter')),
                'reciters' => collect(Quran::RECITERS)->map(fn ($r) => ['name' => $r['name'], 'bitrate' => $r['bitrate']]),
                'surahs' => $surahs,
                'pages' => $mushaf->pages(),
                'ayahPages' => $mushaf->ayahPages(),
                'juz' => $mushaf->juzStarts(),
            ],
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate(['page' => 'required|integer|min:1|max:'.Mushaf::PAGES]);
        $request->user()->setPrefs(['mushaf_page' => $data['page']]);

        return response()->noContent();
    }
}
