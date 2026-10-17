<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityDay;
use App\Models\Book;
use App\Models\MemorizedAyah;
use App\Models\User;
use App\Services\Library;
use App\Services\Telegram;

class DashboardController extends Controller
{
    public function index(Telegram $telegram)
    {
        return view('admin.dashboard', [
            'books' => Book::query()->count(),
            'memorized' => MemorizedAyah::query()->count(),
            'streak' => ActivityDay::streak(),
            'devices' => User::reader()->pushSubscriptions()->count(),
            'week' => ActivityDay::query()->where('date', '>=', now()->subDays(6)->toDateString())->orderBy('date')->get(),
            'telegram' => $telegram->enabled(),
            'vapid' => (bool) config('webpush.vapid.public_key'),
            'disk' => Library::disk(),
        ]);
    }
}
