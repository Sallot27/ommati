<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemorizedAyah extends Model
{
    protected $guarded = [];

    protected $casts = [
        'next_review_on' => 'date',
        'memorized_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
    ];

    /** Days until the next review for each spaced-repetition level. */
    public const INTERVALS = [1, 2, 4, 7, 14, 30, 60];

    public static function intervalFor(int $level): int
    {
        return self::INTERVALS[min($level, count(self::INTERVALS) - 1)];
    }
}
