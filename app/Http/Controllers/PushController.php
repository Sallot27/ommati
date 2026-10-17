<?php

namespace App\Http\Controllers;

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

        $request->user()->updatePushSubscription($data['endpoint'], $data['keys']['p256dh'], $data['keys']['auth'], 'aes128gcm');

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $data = $request->validate(['endpoint' => 'required|string']);
        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->json(['ok' => true]);
    }

    public function test(Request $request)
    {
        $request->user()->notify(new Reminder('تجربة الإشعارات', 'الإشعارات تعمل بإذن الله ✓', url('/')));

        return response()->json(['ok' => true]);
    }
}
