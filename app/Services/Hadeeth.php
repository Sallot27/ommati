<?php

namespace App\Services;

use App\Models\Hadith;
use App\Models\HadithPool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Hadith with explanation and benefits from HadeethEnc.com
 * (موسوعة الأحاديث النبوية — reviewed explanations).
 */
class Hadeeth
{
    private const BASE = 'https://hadeethenc.com/api/v1';

    /** Practical, everyday categories used for the daily hadith. */
    public const DAILY_CATEGORIES = [
        277 => 'فضائل الأعمال الصالحة',
        278 => 'فضائل الذكر',
        280 => 'فضائل أعمال القلوب',
        281 => 'فضائل أعمال الجوارح',
        282 => 'الأخلاق الحميدة',
        273 => 'بر الوالدين',
        274 => 'صلة الأرحام',
        279 => 'فضل الدعاء',
        300 => 'فوائد ذكر الله',
        313 => 'الأدعية المأثورة',
        314 => 'أعمال القلوب',
        321 => 'التوبة',
        322 => 'تزكية النفوس',
        287 => 'آداب الكلام والصمت',
        511 => 'صدقة التطوع',
        484 => 'قيام الليل',
        272 => 'فضائل القرآن الكريم',
        406 => 'الشمائل المحمدية',
    ];

    /** Fill the local pool of hadith ids from the daily categories. */
    public function syncPool(): int
    {
        $count = 0;
        foreach (array_keys(self::DAILY_CATEGORIES) as $categoryId) {
            $page = 1;
            do {
                try {
                    $res = Http::timeout(15)->retry(2, 500, throw: false)->get(self::BASE.'/hadeeths/list/', [
                        'language' => 'ar', 'category_id' => $categoryId, 'page' => $page, 'per_page' => 100,
                    ]);
                } catch (Throwable $e) {
                    Log::warning('HadeethEnc list failed', ['category' => $categoryId, 'error' => $e->getMessage()]);

                    return $count; // site unreachable: don't wait on every category
                }
                if (! $res->successful()) {
                    if ($count === 0) {
                        return 0;
                    }
                    break;
                }
                foreach ((array) $res->json('data') as $row) {
                    HadithPool::query()->updateOrCreate(
                        ['id' => (int) $row['id']],
                        ['title' => (string) $row['title'], 'category_id' => $categoryId],
                    );
                    $count++;
                }
                $last = (int) $res->json('meta.last_page', 1);
                $page++;
            } while ($page <= $last && $page <= 10);
        }

        return $count;
    }

    /** Get a hadith (from local cache, or fetched from HadeethEnc). */
    public function find(int $id): ?Hadith
    {
        $local = Hadith::query()->find($id);
        if ($local) {
            return $local;
        }

        try {
            $res = Http::timeout(15)->retry(2, 500, throw: false)->get(self::BASE.'/hadeeths/one/', ['language' => 'ar', 'id' => $id]);
        } catch (Throwable $e) {
            Log::warning('HadeethEnc fetch failed', ['id' => $id, 'error' => $e->getMessage()]);

            return null;
        }
        if (! $res->successful() || ! $res->json('hadeeth')) {
            return null;
        }

        $j = $res->json();

        return Hadith::query()->create([
            'id' => (int) $j['id'],
            'title' => (string) ($j['title'] ?? ''),
            'text' => (string) $j['hadeeth'],
            'attribution' => $j['attribution'] ?? null,
            'grade' => $j['grade'] ?? null,
            'explanation' => $j['explanation'] ?? null,
            'hints' => array_values(array_filter((array) ($j['hints'] ?? []))),
            'words_meanings' => array_values((array) ($j['words_meanings'] ?? [])),
            'reference' => $j['reference'] ?? null,
        ]);
    }
}
