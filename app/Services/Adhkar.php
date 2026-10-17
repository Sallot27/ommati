<?php

namespace App\Services;

/** Bundled Hisn al-Muslim adhkar. */
class Adhkar
{
    private static ?array $data = null;

    /** Categories shown as the main daily cards, in order. */
    public const FEATURED = ['morning', 'evening', 'after-prayer', 'sleep', 'waking', 'tasbih', 'istighfar', 'salawat', 'distress', 'worry', 'ruqyah-quran', 'ruqyah-sunnah'];

    public function data(): array
    {
        return self::$data ??= json_decode((string) file_get_contents(resource_path('data/adhkar.json')), true);
    }

    public function categories(): array
    {
        return $this->data()['categories'];
    }

    public function find(string $slug): ?array
    {
        foreach ($this->categories() as $c) {
            if ($c['slug'] === $slug) {
                return $c;
            }
        }

        return null;
    }

    public function featured(): array
    {
        return array_values(array_filter(array_map(fn ($s) => $this->find($s), self::FEATURED)));
    }

    public function others(): array
    {
        return array_values(array_filter($this->categories(), fn ($c) => ! in_array($c['slug'], self::FEATURED, true)));
    }
}
