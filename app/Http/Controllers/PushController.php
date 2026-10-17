<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\Reminder;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|url|max:1000',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        User::reader()->updatePushSubscription($data['endpoint'], $data['keys']['p256dh'], $data['keys']['auth'], 'aes128gcm');

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $data = $request->validate(['endpoint' => 'required|string']);
        User::reader()->deletePushSubscription($data['endpoint']);

        return response()->json(['ok' => true]);
    }

    public function test()
    {
        User::reader()->notify(new Reminder('تجربة الإشعارات', 'الإشعارات تعمل بإذن الله ✓', url('/')));

        return response()->json(['ok' => true]);
    }
}
