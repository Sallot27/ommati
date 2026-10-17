<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Services\Quran;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('settings', [
            'user' => $user,
            'reciters' => Quran::RECITERS,
            'reciter' => Quran::reciter($user->pref('reciter')),
            'target' => (int) $user->pref('hifz_daily_target'),
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
            'name' => 'nullable|string|min:2|max:40',
        ]);
        $user = $request->user();

        if (! empty($data['name']) && ! $user->is_admin) {
            $name = User::normaliseName($data['name']);
            $taken = User::query()->where('is_admin', false)->where('name', $name)->whereKeyNot($user->id)->exists();
            if ($taken) {
                return back()->withErrors(['name' => 'هذا الاسم مستخدم، اختاري اسماً آخر.']);
            }
            $user->update(['name' => $name]);
        }

        $prefs = array_filter([
            'reciter' => $data['reciter'] ?? null,
            'hifz_daily_target' => isset($data['hifz_daily_target']) ? (int) $data['hifz_daily_target'] : null,
        ], fn ($v) => $v !== null);
        if ($prefs) {
            $user->setPrefs($prefs);
        }

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return back()->with('status', 'تم الحفظ');
    }
}
