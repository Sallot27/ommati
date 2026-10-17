<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityDay;
use App\Models\Book;
use App\Models\MemorizedAyah;
use App\Models\User;
use App\Services\Library;
use App\Services\Telegram;
use NotificationChannels\WebPush\PushSubscription;

class DashboardController extends Controller
{
    public function index(Telegram $telegram)
    {
        return view('admin.dashboard', [
            'books' => Book::query()->count(),
            'memorized' => MemorizedAyah::query()->count(),
            'devices' => PushSubscription::query()->count(),
            'readers' => User::query()->where('is_admin', false)->withCount('memorized')->orderBy('name')->get(),
            'week' => ActivityDay::query()->with('user')->where('date', '>=', now()->subDays(6)->toDateString())->orderByDesc('date')->get(),
            'telegram' => $telegram->enabled(),
            'vapid' => (bool) config('webpush.vapid.public_key'),
            'disk' => Library::disk(),
        ]);
    }
}
