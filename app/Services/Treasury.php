<?php

namespace App\Services;

/** Allah's names, du'as from the Quran, and the imams' sayings (all bundled, all sourced). */
class Treasury
{
    private static array $cache = [];

    private function load(string $file): array
    {
        return self::$cache[$file] ??= json_decode((string) file_get_contents(resource_path("data/$file")), true);
    }

    public function names(): array
    {
        return $this->load('names.json');
    }

    public function quotesData(): array
    {
        return $this->load('quotes.json');
    }

    private static function dayNumber(): int
    {
        return (int) floor(now()->startOfDay()->getTimestamp() / 86400);
    }

    public function nameOfDay(): array
    {
        $names = $this->names();

        return $names[self::dayNumber() % count($names)] + ['index' => self::dayNumber() % count($names)];
    }

    /** Rotates through the imams so each day brings a different voice. */
    public function quoteOfDay(): array
    {
        $data = $this->quotesData();
        $featured = array_values(array_filter($data['quotes'], fn ($q) => $q['featured']));
        $byImam = [];
        foreach ($featured as $i => $q) {
            $byImam[$q['who']][] = $q;
        }
        $order = array_values(array_filter(array_keys($data['imams']), fn ($k) => isset($byImam[$k])));
        $day = self::dayNumber();
        $imam = $order[$day % count($order)];
        $list = $byImam[$imam];

        return $list[intdiv($day, count($order)) * 37 % count($list)];
    }

    public function duas(): array
    {
        return config('quran_duas');
    }
}
