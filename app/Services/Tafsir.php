<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tafsir texts from the open spa5k/tafsir_api dataset (sourced from quran.com),
 * fetched once per ayah and cached forever.
 */
class Tafsir
{
    public const SOURCES = [
        'muyassar' => ['slug' => 'ar-tafsir-muyassar', 'name' => 'التفسير الميسّر', 'by' => 'نخبة من العلماء — مجمع الملك فهد'],
        'mukhtasar' => ['slug' => 'ar-tafsir-al-mukhtasar', 'name' => 'المختصر في التفسير', 'by' => 'مركز تفسير للدراسات القرآنية'],
        'saadi' => ['slug' => 'ar-tafseer-al-saddi', 'name' => 'تفسير السعدي', 'by' => 'الشيخ عبدالرحمن بن ناصر السعدي'],
        'tadabbur' => ['slug' => 'tadabbur-wa-amal', 'name' => 'تدبّر وعمل', 'by' => 'مركز تدبّر للاستشارات التربوية والتعليمية'],
    ];

    private const MIRRORS = [
        'https://cdn.jsdelivr.net/gh/spa5k/tafsir_api@main/tafsir/%s/%d/%d.json',
        'https://raw.githubusercontent.com/spa5k/tafsir_api/main/tafsir/%s/%d/%d.json',
    ];

    public function text(string $source, int $surah, int $ayah): ?string
    {
        $slug = self::SOURCES[$source]['slug'] ?? null;
        if ($slug === null) {
            return null;
        }

        $key = "tafsir.$source.$surah.$ayah";
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached === '' ? null : $cached;
        }

        foreach (self::MIRRORS as $pattern) {
            try {
                $res = Http::timeout(8)->retry(1, 300, throw: false)->get(sprintf($pattern, $slug, $surah, $ayah));
                if ($res->successful()) {
                    $text = trim((string) $res->json('text'));
                    Cache::forever($key, $text);

                    return $text === '' ? null : $text;
                }
                if ($res->status() === 404) {
                    Cache::put($key, '', now()->addDay());

                    return null;
                }
            } catch (Throwable $e) {
                Log::warning('Tafsir fetch failed', ['source' => $source, 'ref' => "$surah:$ayah", 'error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Tafsir for an ayah range, de-duplicating entries that cover several ayat.
     *
     * @return list<string>
     */
    public function range(string $source, int $surah, int $from, int $to): array
    {
        $out = [];
        for ($a = $from; $a <= $to; $a++) {
            $t = $this->text($source, $surah, $a);
            if ($t !== null && ! in_array($t, $out, true)) {
                $out[] = $t;
            }
        }

        return $out;
    }

    /**
     * Split a "Tadabbur wa Amal" entry into its sections, and pick the
     * reflections that quote the given ayah text.
     *
     * @return array{actions: list<string>, guidance: list<string>, reflections: list<array{quote: string, body: string, question: ?string}>}
     */
    public function tadabbur(int $surah, int $ayah, string $ayahText): array
    {
        $raw = $this->text('tadabbur', $surah, $ayah);
        $result = ['actions' => [], 'guidance' => [], 'reflections' => []];
        if ($raw === null) {
            return $result;
        }

        $sections = preg_split('/^(الوقفات التدبرية|التوجيهات|العمل بالآيات|معاني الكلمات)\s*$/mu', $raw, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $map = [];
        for ($i = 0; $i < count($sections) - 1; $i++) {
            if (in_array(trim($sections[$i]), ['الوقفات التدبرية', 'التوجيهات', 'العمل بالآيات', 'معاني الكلمات'], true)) {
                $map[trim($sections[$i])] = trim($sections[$i + 1]);
                $i++;
            }
        }

        $items = fn (?string $block) => $block === null ? [] : array_values(array_filter(array_map(
            fn ($line) => trim(preg_replace('/^[٠-٩0-9]+\s*[-–]\s*/u', '', $line)),
            preg_split('/\n+/u', $block)
        )));

        $needle = self::bare($ayahText);

        // Items about this exact ayah come first; the rest cover neighbouring ayat on the same page.
        $byRelevance = function (array $list) use ($needle) {
            $hit = $rest = [];
            foreach ($list as $item) {
                $isHit = false;
                if (preg_match('/﴿(.+?)﴾/su', $item, $m)) {
                    $q = trim(preg_replace('/\([٠-٩0-9]+\)/u', ' ', self::bare($m[1])));
                    $first = implode(' ', array_slice(preg_split('/\s+/u', $q), 0, 3));
                    $isHit = $first !== '' && str_contains($needle, $first);
                }
                $isHit ? $hit[] = $item : $rest[] = $item;
            }

            return array_merge($hit, $rest);
        };

        $result['actions'] = $byRelevance($items($map['العمل بالآيات'] ?? null));
        $result['guidance'] = $byRelevance($items($map['التوجيهات'] ?? null));

        // Reflections: blocks starting with "N- ﴿quote﴾", body, then "السؤال: ..."
        $blocks = preg_split('/\n(?=[٠-٩0-9]+\s*-\s*﴿)/u', "\n".($map['الوقفات التدبرية'] ?? ''));
        foreach ($blocks as $block) {
            if (! preg_match('/﴿(.+?)﴾/su', $block, $m)) {
                continue;
            }
            $quote = self::bare($m[1]);
            $quote = trim(preg_replace('/\([٠-٩0-9]+\)/u', ' ', $quote));
            $firstWords = implode(' ', array_slice(preg_split('/\s+/u', $quote), 0, 3));
            if ($firstWords === '' || ! str_contains($needle, $firstWords)) {
                continue;
            }
            $rest = trim(str_replace($m[0], '', preg_replace('/^[٠-٩0-9]+\s*-\s*/u', '', trim($block))));
            $question = null;
            if (preg_match('/السؤال:\s*(.+)$/su', $rest, $q)) {
                $question = trim($q[1]);
                $rest = trim(str_replace($q[0], '', $rest));
            }
            $result['reflections'][] = ['quote' => trim($m[1]), 'body' => $rest, 'question' => $question];
        }

        return $result;
    }

    /** Remove diacritics and Quranic marks so texts can be compared. */
    public static function bare(string $s): string
    {
        $s = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{08D3}-\x{08FF}\x{0640}]/u', '', $s);
        $s = strtr($s, ['ٱ' => 'ا', 'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', 'ۥ' => '', 'ۦ' => '']);

        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
