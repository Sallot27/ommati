<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityDay extends Model
{
    protected $primaryKey = 'date';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public static function bump(string $column, int $by = 1): void
    {
        $date = now()->toDateString();
        $row = static::query()->firstOrCreate(['date' => $date]);
        $row->increment($column, $by);
    }

    /** Consecutive days (ending today or yesterday) with any activity. */
    public static function streak(): int
    {
        $dates = static::query()
            ->where(fn ($q) => $q->where('ayahs_memorized', '>', 0)->orWhere('ayahs_reviewed', '>', 0)->orWhere('adhkar_sessions', '>', 0))
            ->orderByDesc('date')->limit(400)->pluck('date')
            ->map(fn ($d) => substr((string) $d, 0, 10))->flip();

        $day = now()->startOfDay();
        if (! isset($dates[$day->toDateString()])) {
            $day->subDay();
        }
        $streak = 0;
        while (isset($dates[$day->toDateString()])) {
            $streak++;
            $day->subDay();
        }

        return $streak;
    }
}
