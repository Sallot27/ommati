<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityDay extends Model
{
    protected $guarded = [];

    protected $casts = [
        'morning_done' => 'boolean',
        'evening_done' => 'boolean',
        'ayah_read' => 'boolean',
        'hadith_read' => 'boolean',
        'khatma_read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function bump(User $user, string $column, int $by = 1): void
    {
        $user->today()->increment($column, $by);
    }

    public static function mark(User $user, string $flag): void
    {
        $day = $user->today();
        if (! $day->{$flag}) {
            $day->update([$flag => true]);
        }
    }

    /** Consecutive days (ending today or yesterday) with any activity. */
    public static function streak(User $user): int
    {
        $dates = static::query()->where('user_id', $user->id)
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
