<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Prayer times from AlAdhan (Umm al-Qura method). */
class PrayerTimes
{
    public const NAMES = [
        'Fajr' => 'الفجر',
        'Sunrise' => 'الشروق',
        'Dhuhr' => 'الظهر',
        'Asr' => 'العصر',
        'Maghrib' => 'المغرب',
        'Isha' => 'العشاء',
    ];

    /**
     * @return array{timings: array<string,string>, timezone: string, hijri: ?string, date: string}|null
     */
    public function forDate(Carbon $date, ?float $lat = null, ?float $lng = null): ?array
    {
        $lat ??= (float) Setting::get('location_lat');
        $lng ??= (float) Setting::get('location_lng');
        $key = sprintf('prayer.%s.%.3f.%.3f', $date->toDateString(), $lat, $lng);

        if ($hit = Cache::get($key)) {
            return $hit;
        }

        try {
            $res = Http::timeout(10)->retry(2, 400, throw: false)->get(
                'https://api.aladhan.com/v1/timings/'.$date->format('d-m-Y'),
                ['latitude' => $lat, 'longitude' => $lng, 'method' => 4]
            );
        } catch (Throwable $e) {
            Log::warning('Prayer times fetch failed', ['error' => $e->getMessage()]);

            return null;
        }
        if (! $res->successful()) {
            return null;
        }

        $t = $res->json('data.timings');
        $hijri = $res->json('data.date.hijri');
        $result = [
            'timings' => collect(self::NAMES)->mapWithKeys(fn ($ar, $k) => [$k => substr((string) ($t[$k] ?? ''), 0, 5)])->all(),
            'timezone' => (string) $res->json('data.meta.timezone', config('app.timezone')),
            'hijri' => $hijri ? sprintf('%s %s %s هـ', $hijri['day'], $hijri['month']['ar'] ?? '', $hijri['year']) : null,
            'date' => $date->toDateString(),
        ];
        Cache::put($key, $result, now()->addDays(2));

        return $result;
    }
}
