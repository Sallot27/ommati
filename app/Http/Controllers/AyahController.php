<?php

namespace App\Http\Controllers;

use App\Models\ActivityDay;
use App\Services\Daily;
use App\Services\Quran;
use App\Services\Tafsir;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AyahController extends Controller
{
    public function today(Request $request, Daily $daily, Quran $quran, Tafsir $tafsir)
    {
        $date = now();
        if ($request->filled('d')) {
            $date = Carbon::parse($request->query('d'));
            abort_if($date->isAfter(now()), 404);
        }

        [$s, $from, $to] = $daily->ayahRef($date);
        if ($date->isToday()) {
            ActivityDay::mark($request->user(), 'ayah_read');
        }
        $surah = $quran->surah($s);
        $ayahs = [];
        for ($a = $from; $a <= $to; $a++) {
            $ayahs[] = ['n' => $a, 'text' => $quran->ayah($s, $a), 'audio' => $quran->audioUrl((string) $request->user()->pref('reciter'), $s, $a)];
        }

        $tadabbur = ['actions' => [], 'guidance' => [], 'reflections' => []];
        foreach ($ayahs as $a) {
            $t = $tafsir->tadabbur($s, $a['n'], $a['text']);
            foreach ($t as $k => $items) {
                foreach ($items as $item) {
                    if (! in_array($item, $tadabbur[$k], true)) {
                        $tadabbur[$k][] = $item;
                    }
                }
            }
        }

        return view('ayah', [
            'date' => $date,
            'surah' => $surah,
            'from' => $from,
            'to' => $to,
            'ayahs' => $ayahs,
            'muyassar' => $tafsir->range('muyassar', $s, $from, $to),
            'saadi' => $tafsir->range('saadi', $s, $from, $to),
            'tadabbur' => $tadabbur,
            'sources' => Tafsir::SOURCES,
            'yesterday' => $date->copy()->subDay()->toDateString(),
            'tomorrow' => $date->isToday() ? null : $date->copy()->addDay()->toDateString(),
        ]);
    }
}
