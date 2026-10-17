<?php

namespace App\Http\Controllers;

use App\Services\Quran;
use App\Services\Tafsir;
use Illuminate\Http\Request;

class QuranController extends Controller
{
    public function index(Request $request, Quran $quran)
    {
        $lastRead = $request->user()->pref('last_read');

        return view('quran.index', [
            'surahs' => $quran->index(),
            'lastRead' => is_array($lastRead) && $quran->exists((int) $lastRead['s'], (int) $lastRead['a'])
                ? $lastRead + ['name' => $quran->surah((int) $lastRead['s'])['name']] : null,
        ]);
    }

    public function show(Request $request, Quran $quran, int $surah)
    {
        $s = $quran->surah($surah) ?? abort(404);

        return view('quran.show', [
            'surah' => $s,
            'prev' => $quran->surah($surah - 1),
            'next' => $quran->surah($surah + 1),
            'reciters' => Quran::RECITERS,
            'reciter' => Quran::reciter($request->user()->pref('reciter')),
        ]);
    }

    /** Ayah texts and audio URLs for the player and the memorization screen. */
    public function data(Request $request, Quran $quran, int $surah)
    {
        $s = $quran->surah($surah) ?? abort(404);
        $reciter = Quran::reciter($request->query('reciter', $request->user()->pref('reciter')));

        return response()->json([
            'n' => $s['n'],
            'name' => $s['name'],
            'count' => $s['count'],
            'reciter' => $reciter,
            'ayahs' => collect($s['ayahs'])->map(fn ($text, $i) => [
                'n' => $i + 1,
                'text' => $text,
                'page' => $s['pages'][$i],
                'audio' => $quran->audioUrl($reciter, $surah, $i + 1),
            ])->all(),
        ])->header('Cache-Control', 'public, max-age=86400');
    }

    public function tafsir(Request $request, Quran $quran, Tafsir $tafsir, int $surah, int $ayah)
    {
        abort_unless($quran->exists($surah, $ayah), 404);
        $source = $request->query('source', 'muyassar');
        abort_unless(isset(Tafsir::SOURCES[$source]), 404);

        return response()->json([
            'source' => Tafsir::SOURCES[$source],
            'text' => $tafsir->text($source, $surah, $ayah),
        ]);
    }

    public function lastRead(Request $request, Quran $quran)
    {
        $data = $request->validate(['s' => 'required|integer', 'a' => 'required|integer']);
        abort_unless($quran->exists($data['s'], $data['a']), 422);
        $request->user()->setPrefs(['last_read' => ['s' => $data['s'], 'a' => $data['a']]]);

        return response()->noContent();
    }

    public function listen(Request $request, Quran $quran)
    {
        return view('quran.listen', [
            'surahs' => $quran->index(),
            'reciters' => Quran::RECITERS,
            'reciter' => Quran::reciter($request->user()->pref('reciter')),
        ]);
    }
}
