<?php

namespace App\Services;

use RuntimeException;

/**
 * Page index of the Madinah Mushaf (KFGQPC, 1441 AH edition, 604 pages, 15 lines).
 * Page layouts live in public/mushaf-data/pages/NNN.json (built from quran-qcf4, MIT data);
 * the glyph fonts are the King Fahd Complex QCF4 fonts, loaded by the browser from a CDN.
 */
class Mushaf
{
    public const PAGES = 604;

    private static ?array $data = null;

    private function data(): array
    {
        if (self::$data === null) {
            $data = json_decode((string) file_get_contents(resource_path('data/mushaf.json')), true);
            if (! is_array($data)) {
                throw new RuntimeException('Mushaf index missing.');
            }
            self::$data = $data;
        }

        return self::$data;
    }

    /** Page on which an ayah starts. */
    public function pageOf(int $surah, int $ayah = 1): ?int
    {
        return $this->data()['ayahPage'][(string) $surah][$ayah - 1] ?? null;
    }

    /** @return array<string, list<int>> surah => start page of every ayah */
    public function ayahPages(): array
    {
        return $this->data()['ayahPage'];
    }

    /** @return list<array{s: list<int>, j: int}> per page (0-based): surahs on it and its juz */
    public function pages(): array
    {
        return $this->data()['pages'];
    }

    /** @return array<int, int> juz => first page */
    public function juzStarts(): array
    {
        $starts = [];
        foreach ($this->pages() as $i => $p) {
            $starts[$p['j']] ??= $i + 1;
        }

        return $starts;
    }

    public static function fontUrl(): string
    {
        return rtrim((string) config('services.mushaf.font_url'), '/');
    }
}
