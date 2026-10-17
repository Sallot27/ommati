<?php

namespace App\Services;

use App\Models\DailyPick;
use App\Models\Hadith;
use App\Models\HadithPool;
use Illuminate\Support\Carbon;

/** Chooses (and remembers) the ayah and hadith of the day. */
class Daily
{
    public function __construct(private Quran $quran, private Hadeeth $hadeeth) {}

    private function dayNumber(Carbon $date): int
    {
        return (int) floor($date->copy()->startOfDay()->getTimestamp() / 86400);
    }

    public function pick(?Carbon $date = null): DailyPick
    {
        $date ??= now();

        return DailyPick::query()->firstOrCreate(['date' => $date->toDateString()]);
    }

    /** @return array{0:int,1:int,2:int} [surah, from, to] */
    public function ayahRef(?Carbon $date = null): array
    {
        $date ??= now();
        $pick = $this->pick($date);
        if (! $pick->ayah_ref) {
            $list = config('daily_ayahs');
            $pick->ayah_ref = $list[$this->dayNumber($date) % count($list)];
            $pick->save();
        }

        return Quran::parseRef($pick->ayah_ref);
    }

    public function hadith(?Carbon $date = null): ?Hadith
    {
        $date ??= now();
        $pick = $this->pick($date);

        if ($pick->hadith_id && ($h = $this->hadeeth->find($pick->hadith_id))) {
            return $h;
        }

        if (! HadithPool::query()->exists()) {
            $this->hadeeth->syncPool();
        }
        $total = HadithPool::query()->count();
        if ($total === 0) {
            // Offline fallback: most recent hadith we already have.
            return Hadith::query()->latest('updated_at')->first();
        }

        // Walk the pool with a stride so consecutive days vary in topic.
        $start = ($this->dayNumber($date) * 7919) % $total;
        for ($i = 0; $i < 5; $i++) {
            $id = HadithPool::query()->orderBy('id')->skip(($start + $i) % $total)->value('id');
            if ($id && ($h = $this->hadeeth->find((int) $id))) {
                $pick->hadith_id = $h->id;
                $pick->save();

                return $h;
            }
        }

        return Hadith::query()->latest('updated_at')->first();
    }
}
