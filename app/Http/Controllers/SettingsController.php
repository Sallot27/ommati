<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Quran;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings', [
            'reciters' => Quran::RECITERS,
            'reciter' => Quran::reciter(Setting::get('reciter')),
            'target' => (int) Setting::get('hifz_daily_target'),
            'vapid' => config('webpush.vapid.public_key'),
            'reminders' => [
                'morning' => Setting::get('reminder_morning'),
                'evening' => Setting::get('reminder_evening'),
                'hifz' => Setting::get('reminder_hifz'),
                'daily' => Setting::get('reminder_daily'),
                'sleep' => Setting::get('reminder_sleep'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'reciter' => 'nullable|in:'.implode(',', array_keys(Quran::RECITERS)),
            'hifz_daily_target' => 'nullable|integer|min:1|max:20',
        ]);
        Setting::putMany(array_filter($data, fn ($v) => $v !== null));

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return back()->with('status', 'تم الحفظ');
    }
}
