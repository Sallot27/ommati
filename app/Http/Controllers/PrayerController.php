<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\PrayerTimes;
use Illuminate\Http\Request;

class PrayerController extends Controller
{
    public function index()
    {
        return view('prayer', [
            'lat' => (float) Setting::get('location_lat'),
            'lng' => (float) Setting::get('location_lng'),
            'place' => Setting::get('location_name'),
            'cities' => config('cities'),
        ]);
    }

    public function times(Request $request, PrayerTimes $prayer)
    {
        $data = $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);
        $lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) ? (float) $data['lng'] : null;

        $today = $prayer->forDate(now(), $lat, $lng);
        $tomorrow = $prayer->forDate(now()->addDay(), $lat, $lng);
        abort_if($today === null, 503, 'تعذّر جلب مواقيت الصلاة الآن.');

        return response()->json(['today' => $today, 'tomorrow' => $tomorrow, 'names' => PrayerTimes::NAMES]);
    }

    /** Remember the reader's location, used for prayer reminders. */
    public function location(Request $request)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'name' => 'nullable|string|max:80',
        ]);
        Setting::putMany([
            'location_lat' => (string) round($data['lat'], 4),
            'location_lng' => (string) round($data['lng'], 4),
            'location_name' => $data['name'] ?: 'موقعي الحالي',
        ]);

        return response()->noContent();
    }
}
