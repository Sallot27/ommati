<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** Defaults used when nothing has been saved yet. */
    public const DEFAULTS = [
        'aunt_name' => 'عمتي الغالية',
        'app_title' => 'رفيق الطاعة',
        'reminder_morning' => '06:30',
        'reminder_evening' => '16:30',
        'reminder_hifz' => '10:00',
        'reminder_daily' => '08:00',
        'reminder_sleep' => '22:00',
        'reminder_fasting' => '20:30',
        'reminder_friday' => '09:30',
        'reminders_enabled' => '1',
        'prayer_reminders' => '1',
        'location_lat' => '24.7136',
        'location_lng' => '46.6753',
        'location_name' => 'الرياض',
        'hifz_daily_target' => '3',
        'reciter' => 'ar.husary',
        'last_read' => null,
        'tasbih_total' => '0',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }

    /** @param array<string, mixed> $values */
    public static function putMany(array $values): void
    {
        foreach ($values as $k => $v) {
            static::query()->updateOrCreate(['key' => $k], ['value' => $v]);
        }
        Cache::forget('settings.all');
    }
}
