<?php

namespace App\Http\Controllers;

use App\Models\ActivityDay;
use App\Services\Hadeeth;
use App\Services\Hijri;
use App\Services\Khatma;
use App\Services\Occasions;
use App\Services\Quran;
use App\Services\Tafsir;
use App\Services\Treasury;
use Illuminate\Http\Request;

/** Names of Allah, Quranic du'as, the imams' sayings, virtuous days, and the khatma plan. */
class TreasuryController extends Controller
{
    public function names(Treasury $treasury, Quran $quran)
    {
        return view('names.index', [
            'names' => $treasury->names(),
            'today' => $treasury->nameOfDay(),
        ]);
    }

    public function name(Treasury $treasury, Quran $quran, Tafsir $tafsir, int $i)
    {
        $names = $treasury->names();
        $n = $names[$i] ?? abort(404);

        return view('names.show', [
            'i' => $i,
            'n' => $n,
            'ayah' => $quran->ayah($n['surah'], $n['ayah']),
            'surah' => $quran->surah($n['surah'])['name'],
            'muyassar' => $tafsir->text('muyassar', $n['surah'], $n['ayah']),
            'saadi' => $tafsir->text('saadi', $n['surah'], $n['ayah']),
            'prev' => $i > 0 ? $i - 1 : null,
            'next' => $i < count($names) - 1 ? $i + 1 : null,
            'count' => count($names),
        ]);
    }

    public function duas(Treasury $treasury, Quran $quran)
    {
        $duas = array_map(function ($d) use ($quran) {
            [$s, $from, $to] = Quran::parseRef($d[0]);
            $ayahs = [];
            for ($a = $from; $a <= $to; $a++) {
                $ayahs[] = ['n' => $a, 'text' => $quran->ayah($s, $a)];
            }

            return ['ref' => $d[0], 'who' => $d[1], 's' => $s, 'from' => $from, 'surah' => $quran->surah($s)['name'], 'ayahs' => $ayahs];
        }, $treasury->duas());

        return view('duas', ['duas' => $duas]);
    }

    public function quotes(Request $request, Treasury $treasury, Quran $quran)
    {
        $data = $treasury->quotesData();
        $who = $request->query('who');
        abort_if($who !== null && ! isset($data['imams'][$who]), 404);

        $list = array_values(array_filter($data['quotes'], fn ($q) => $who ? $q['who'] === $who : $q['featured']));
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;
        // Stable shuffle so the page isn't dominated by the first surahs.
        usort($list, fn ($a, $b) => crc32($a['text']) <=> crc32($b['text']));

        return view('quotes', [
            'imams' => $data['imams'],
            'counts' => array_count_values(array_column($data['quotes'], 'who')),
            'who' => $who,
            'quotes' => array_slice($list, ($page - 1) * $per, $per),
            'page' => $page,
            'more' => count($list) > $page * $per,
            'today' => $treasury->quoteOfDay(),
            'surahName' => fn ($n) => $quran->surah($n)['name'],
            'source' => $data['source'],
        ]);
    }

    public function days(Occasions $occasions, Hadeeth $hadeeth)
    {
        $today = $occasions->on(now());

        return view('days', [
            'hijri' => Hijri::format(now()),
            'today' => array_map(fn ($o) => $o + ['h' => $o['hadith'] ? $hadeeth->find($o['hadith']) : null], $today),
            'upcoming' => $occasions->upcoming(now(), 60),
            'weekly' => [
                ['title' => 'صيام الاثنين والخميس', 'hadith' => 6183],
                ['title' => 'صيام ثلاثة أيام من كل شهر (الأيام البيض)', 'hadith' => 10108],
                ['title' => 'ساعة الإجابة يوم الجمعة', 'hadith' => 65753],
                ['title' => 'آداب يوم الجمعة', 'hadith' => 65756],
            ],
        ]);
    }

    public function khatma(Request $request, Khatma $khatma)
    {
        $plan = $khatma->plan($request->user());

        return view('khatma.index', [
            'plan' => $plan,
            'plans' => Khatma::PLANS,
            'portion' => $plan ? $khatma->portion($plan) : null,
            'perDay' => $plan ? $khatma->perDay($plan) : null,
            'readToday' => (bool) $request->user()->today()->khatma_read,
        ]);
    }

    public function khatmaStart(Request $request, Khatma $khatma)
    {
        $data = $request->validate(['days' => 'required|integer|in:'.implode(',', array_keys(Khatma::PLANS))]);
        $khatma->start($request->user(), (int) $data['days']);

        return redirect()->route('khatma.read');
    }

    public function khatmaStop(Request $request, Khatma $khatma)
    {
        $khatma->stop($request->user());

        return redirect()->route('khatma');
    }

    public function khatmaRead(Request $request, Khatma $khatma)
    {
        $plan = $khatma->plan($request->user()) ?? abort(404);
        $portion = $khatma->portion($plan);

        return view('khatma.read', [
            'plan' => $plan,
            'portion' => $portion,
            'groups' => $khatma->pages($portion['from'], $portion['to']),
        ]);
    }

    public function khatmaDone(Request $request, Khatma $khatma)
    {
        $finished = $khatma->advance($request->user());
        ActivityDay::mark($request->user(), 'khatma_read');

        return redirect()->route('khatma')->with('status', $finished
            ? 'ما شاء الله! أتممتِ ختم القرآن الكريم 🌿 تقبّل الله منكِ'
            : 'تقبّل الله منكِ، أتممتِ ورد اليوم');
    }
}
