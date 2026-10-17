<?php

namespace App\Http\Controllers;

use App\Services\PrayerTimes;
use Illuminate\Http\Request;

class PrayerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('prayer', [
            'lat' => (float) $user->pref('location_lat'),
            'lng' => (float) $user->pref('location_lng'),
            'place' => $user->pref('location_name'),
            'cities' => config('cities'),
        ]);
    }

    public function times(Request $request, PrayerTimes $prayer)
    {
        $data = $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);
        $lat = isset($data['lat']) ? (float) $data['lat'] : (float) $request->user()->pref('location_lat');
        $lng = isset($data['lng']) ? (float) $data['lng'] : (float) $request->user()->pref('location_lng');

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
        $request->user()->setPrefs([
            'location_lat' => round((float) $data['lat'], 4),
            'location_lng' => round((float) $data['lng'], 4),
            'location_name' => $data['name'] ?: 'موقعي الحالي',
        ]);

        return response()->noContent();
    }
}
