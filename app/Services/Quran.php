<?php

namespace App\Services;

use RuntimeException;

/**
 * Read-only access to the bundled Quran text (Tanzil Uthmani, Hafs) and
 * Madinah-mushaf page / juz metadata. Text is never modified.
 */
class Quran
{
    /** @var array<int, array<string, mixed>>|null */
    private static ?array $surahs = null;

    /** Reciters available as per-ayah audio on cdn.islamic.network. */
    public const RECITERS = [
        'ar.husary' => ['name' => 'محمود خليل الحصري', 'bitrate' => 128, 'note' => 'مرتّل وواضح — الأنسب للحفظ'],
        'ar.minshawi' => ['name' => 'محمد صديق المنشاوي', 'bitrate' => 128, 'note' => 'مرتّل'],
        'ar.alafasy' => ['name' => 'مشاري راشد العفاسي', 'bitrate' => 128, 'note' => 'مرتّل'],
        'ar.mahermuaiqly' => ['name' => 'ماهر المعيقلي', 'bitrate' => 128, 'note' => 'إمام الحرم المكي'],
        'ar.abdurrahmaansudais' => ['name' => 'عبدالرحمن السديس', 'bitrate' => 192, 'note' => 'إمام الحرم المكي'],
        'ar.saoodshuraym' => ['name' => 'سعود الشريم', 'bitrate' => 64, 'note' => 'إمام الحرم المكي'],
        'ar.hudhaify' => ['name' => 'علي الحذيفي', 'bitrate' => 128, 'note' => 'إمام الحرم النبوي'],
        'ar.abdulbasitmurattal' => ['name' => 'عبدالباسط عبدالصمد', 'bitrate' => 192, 'note' => 'مرتّل'],
        'ar.shaatree' => ['name' => 'أبو بكر الشاطري', 'bitrate' => 128, 'note' => 'مرتّل'],
        'ar.muhammadayyoub' => ['name' => 'محمد أيوب', 'bitrate' => 128, 'note' => 'مرتّل'],
    ];

    /** @return array<int, array<string, mixed>> keyed by surah number */
    public function all(): array
    {
        if (self::$surahs === null) {
            $path = resource_path('data/quran.json');
            $data = json_decode((string) file_get_contents($path), true);
            if (! is_array($data)) {
                throw new RuntimeException('Quran data missing.');
            }
            self::$surahs = [];
            foreach ($data['surahs'] as $s) {
                self::$surahs[$s['n']] = $s;
            }
        }

        return self::$surahs;
    }

    /** Surah list without the ayah text. */
    public function index(): array
    {
        return array_map(fn ($s) => [
            'n' => $s['n'],
            'name' => $s['name'],
            'type' => $s['type'],
            'count' => $s['count'],
            'page' => $s['pages'][0],
            'juz' => $s['juz'][0],
        ], array_values($this->all()));
    }

    public function surah(int $n): ?array
    {
        return $this->all()[$n] ?? null;
    }

    public function exists(int $surah, int $ayah = 1): bool
    {
        $s = $this->surah($surah);

        return $s !== null && $ayah >= 1 && $ayah <= $s['count'];
    }

    public function ayah(int $surah, int $ayah): ?string
    {
        return $this->surah($surah)['ayahs'][$ayah - 1] ?? null;
    }

    public function globalNumber(int $surah, int $ayah): int
    {
        return $this->surah($surah)['start'] + $ayah - 1;
    }

    public function page(int $surah, int $ayah): int
    {
        return $this->surah($surah)['pages'][$ayah - 1];
    }

    public static function reciter(?string $id): string
    {
        return isset(self::RECITERS[$id]) ? $id : 'ar.husary';
    }

    public function audioUrl(string $reciter, int $surah, int $ayah): string
    {
        $reciter = self::reciter($reciter);
        $bitrate = self::RECITERS[$reciter]['bitrate'];

        return "https://cdn.islamic.network/quran/audio/{$bitrate}/{$reciter}/{$this->globalNumber($surah, $ayah)}.mp3";
    }

    /** Arabic-Indic digits for display. */
    public static function digits(int|string $n): string
    {
        return strtr((string) $n, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
    }

    /** Parse "2:255" or "94:5-8" into [surah, from, to]. */
    public static function parseRef(string $ref): array
    {
        [$s, $range] = explode(':', $ref);
        $parts = explode('-', $range);

        return [(int) $s, (int) $parts[0], (int) ($parts[1] ?? $parts[0])];
    }
}
