<?php

use App\Http\Controllers\AdhkarController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AyahController;
use App\Http\Controllers\HadithController;
use App\Http\Controllers\HifzController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\MushafController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PrayerController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\QuranController;
use App\Http\Controllers\ReaderAuthController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TreasuryController;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

// الدخول بالاسم
Route::get('/hello', [ReaderAuthController::class, 'show'])->name('hello');
Route::post('/hello', [ReaderAuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/bye', [ReaderAuthController::class, 'logout'])->name('bye');
Route::view('/offline', 'offline')->name('offline');

Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // القرآن والتلاوات
    Route::get('/quran', [QuranController::class, 'index'])->name('quran.index');
    Route::get('/quran/{surah}', [QuranController::class, 'show'])->whereNumber('surah')->name('quran.show');
    Route::get('/api/quran/{surah}', [QuranController::class, 'data'])->whereNumber('surah')->name('quran.data');
    Route::get('/api/tafsir/{surah}/{ayah}', [QuranController::class, 'tafsir'])->whereNumber(['surah', 'ayah'])->name('quran.tafsir');
    Route::post('/quran/last-read', [QuranController::class, 'lastRead'])->name('quran.last-read');
    Route::get('/mushaf/{page?}', [MushafController::class, 'show'])->whereNumber('page')->name('mushaf');
    Route::post('/mushaf-page', [MushafController::class, 'save'])->name('mushaf.save');
    Route::get('/listen', [QuranController::class, 'listen'])->name('listen');

    // آية اليوم وحديث اليوم
    Route::get('/ayah', [AyahController::class, 'today'])->name('ayah.today');
    Route::get('/hadith', [HadithController::class, 'today'])->name('hadith.today');
    Route::get('/hadith/saved', [HadithController::class, 'saved'])->name('hadith.saved');
    Route::get('/hadith/{id}', [HadithController::class, 'show'])->whereNumber('id')->name('hadith.show');
    Route::post('/hadith/{id}/save', [HadithController::class, 'toggleSave'])->whereNumber('id')->name('hadith.save');

    // الأذكار
    Route::get('/adhkar', [AdhkarController::class, 'index'])->name('adhkar.index');
    Route::get('/adhkar/{slug}', [AdhkarController::class, 'show'])->name('adhkar.show');
    Route::post('/adhkar/{slug}/done', [AdhkarController::class, 'done'])->name('adhkar.done');

    // الحفظ
    Route::get('/hifz', [HifzController::class, 'index'])->name('hifz.index');
    Route::get('/hifz/learn/{surah}', [HifzController::class, 'learn'])->whereNumber('surah')->name('hifz.learn');
    Route::get('/hifz/review', [HifzController::class, 'review'])->name('hifz.review');
    Route::post('/hifz/mark', [HifzController::class, 'mark'])->name('hifz.mark');
    Route::post('/hifz/target', [HifzController::class, 'target'])->name('hifz.target');

    // المكتبة
    Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('/library/{book}', [LibraryController::class, 'show'])->name('library.show');
    Route::get('/library/{book}/file', [LibraryController::class, 'file'])->name('library.file');
    Route::get('/library/{book}/read', [LibraryController::class, 'read'])->name('library.read');
    Route::get('/library/{book}/stream', [LibraryController::class, 'stream'])->name('library.stream');
    Route::get('/library/{book}/cover', [LibraryController::class, 'cover'])->name('library.cover');
    Route::post('/library/{book}/cover', [LibraryController::class, 'storeCover'])->middleware('throttle:30,1')->name('library.cover.store');
    Route::post('/library/{book}/progress', [LibraryController::class, 'progress'])->name('library.progress');
    Route::post('/library/{book}/bookmarks', [LibraryController::class, 'addBookmark'])->name('library.bookmarks.store');
    Route::delete('/library/{book}/bookmarks/{bookmark}', [LibraryController::class, 'deleteBookmark'])->name('library.bookmarks.destroy');

    // كنوز: الأسماء الحسنى، أدعية القرآن، كلام الأئمة، الأيام الفاضلة، الختمة
    Route::get('/names', [TreasuryController::class, 'names'])->name('names');
    Route::get('/names/{i}', [TreasuryController::class, 'name'])->whereNumber('i')->name('names.show');
    Route::get('/duas', [TreasuryController::class, 'duas'])->name('duas');
    Route::get('/quotes', [TreasuryController::class, 'quotes'])->name('quotes');
    Route::get('/days', [TreasuryController::class, 'days'])->name('days');
    Route::get('/khatma', [TreasuryController::class, 'khatma'])->name('khatma');
    Route::post('/khatma/start', [TreasuryController::class, 'khatmaStart'])->name('khatma.start');
    Route::post('/khatma/stop', [TreasuryController::class, 'khatmaStop'])->name('khatma.stop');
    Route::get('/khatma/read', [TreasuryController::class, 'khatmaRead'])->name('khatma.read');
    Route::post('/khatma/done', [TreasuryController::class, 'khatmaDone'])->name('khatma.done');

    // الصلاة والقبلة والسبحة
    Route::get('/prayer', [PrayerController::class, 'index'])->name('prayer');
    Route::get('/api/prayer', [PrayerController::class, 'times'])->name('prayer.times');
    Route::post('/prayer/location', [PrayerController::class, 'location'])->name('prayer.location');
    Route::get('/tasbih', [PageController::class, 'tasbih'])->name('tasbih');
    Route::get('/more', [PageController::class, 'more'])->name('more');
    Route::get('/install', [PageController::class, 'install'])->name('install');

    // الإعدادات والإشعارات
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/push/subscribe', [PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::post('/push/test', [PushController::class, 'test'])->middleware('throttle:5,1')->name('push.test');
});

// بوت تيلغرام
Route::post('/telegram/webhook', TelegramWebhookController::class)->name('telegram.webhook');

// لوحة التحكم
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'show'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('books', Admin\BookController::class)->except(['show']);
        Route::resource('messages', Admin\MessageController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings');
        Route::post('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::post('/telegram/webhook', [Admin\SettingsController::class, 'telegramWebhook'])->name('telegram.webhook');
        Route::post('/telegram/approve', [Admin\SettingsController::class, 'telegramApprove'])->name('telegram.approve');
        Route::post('/telegram/revoke', [Admin\SettingsController::class, 'telegramRevoke'])->name('telegram.revoke');
        Route::post('/push/test', [Admin\SettingsController::class, 'pushTest'])->name('push.test');
    });
});

// PWA manifest (uses the app name set in the admin settings)
Route::get('/manifest.webmanifest', function () {
    $name = Setting::get('app_title');

    return response()->json([
        'name' => $name,
        'short_name' => $name,
        'description' => 'أذكار، وحفظ القرآن، وآية وحديث كل يوم',
        'lang' => 'ar',
        'dir' => 'rtl',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#f5f7f6',
        'theme_color' => '#1f4569',
        'icons' => [
            ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => '/icons/maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'shortcuts' => [
            ['name' => 'أذكار الصباح', 'url' => '/adhkar/morning'],
            ['name' => 'أذكار المساء', 'url' => '/adhkar/evening'],
            ['name' => 'ورد الحفظ', 'url' => '/hifz'],
            ['name' => 'السبحة', 'url' => '/tasbih'],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE);
})->name('manifest');
