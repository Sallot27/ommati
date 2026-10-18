<?php

namespace App\Services;

use DateTimeInterface;
use IntlDateFormatter;

/** Umm al-Qura Hijri dates (the official Saudi calendar). */
class Hijri
{
    public const MONTHS = [1 => 'محرم', 'صفر', 'ربيع الأول', 'ربيع الآخر', 'جمادى الأولى', 'جمادى الآخرة', 'رجب', 'شعبان', 'رمضان', 'شوال', 'ذو القعدة', 'ذو الحجة'];

    /** @return array{day: int, month: int, year: int} */
    public static function parts(DateTimeInterface $date): array
    {
        $f = new IntlDateFormatter('en_US@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE,
            $date->getTimezone()->getName(), IntlDateFormatter::TRADITIONAL, 'd/M/y');
        [$d, $m, $y] = array_map('intval', explode('/', (string) $f->format($date)));

        return ['day' => $d, 'month' => $m, 'year' => $y];
    }

    public static function format(DateTimeInterface $date): string
    {
        $p = self::parts($date);

        return Quran::digits($p['day']).' '.self::MONTHS[$p['month']].' '.Quran::digits($p['year']).' هـ';
    }
}
