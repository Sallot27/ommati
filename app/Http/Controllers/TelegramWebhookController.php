<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\Library;
use App\Services\Telegram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Receives messages sent to the Telegram bot. Any document (PDF, EPUB, …)
 * sent or forwarded to the bot by an allowed account is added to the library.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, Telegram $telegram, Library $library)
    {
        $secret = (string) config('services.telegram.webhook_secret');
        if (! $telegram->enabled() || $secret === '' || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(403);
        }

        $message = $request->input('message') ?? $request->input('channel_post');
        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $messageId = $message['message_id'] ?? null;
        $allowed = config('services.telegram.allowed_chat_ids');

        try {
            if (! in_array($chatId, $allowed, true)) {
                $telegram->sendMessage($chatId, "أهلاً! هذا البوت خاص بمكتبة التطبيق.\nرقم محادثتك: {$chatId}\nأضِفه في إعداد TELEGRAM_ALLOWED_CHAT_IDS لتتمكن من إضافة الكتب.");

                return response()->json(['ok' => true]);
            }

            $text = trim((string) ($message['text'] ?? ''));
            if (str_starts_with($text, '/start') || str_starts_with($text, '/help')) {
                $telegram->sendMessage($chatId, "أرسل أو حوّل لي أي كتاب (PDF أو EPUB) وأضيفه للمكتبة مباشرة.\nاكتب في وصف الملف (Caption):\nالعنوان | المؤلف | التصنيف\nمثال: رياض الصالحين | النووي | حديث\n\n/list آخر الكتب المضافة");

                return response()->json(['ok' => true]);
            }
            if (str_starts_with($text, '/list')) {
                $lines = Book::query()->latest()->limit(10)->get()->map(fn ($b, $i) => ($i + 1).'. '.$b->title)->implode("\n");
                $telegram->sendMessage($chatId, $lines ?: 'المكتبة فارغة حالياً.');

                return response()->json(['ok' => true]);
            }

            $doc = $message['document'] ?? $message['audio'] ?? null;
            if (! $doc) {
                $telegram->sendMessage($chatId, 'أرسل ملف الكتاب نفسه (PDF أو EPUB) وأنا أضيفه 📚', $messageId);

                return response()->json(['ok' => true]);
            }

            $name = (string) ($doc['file_name'] ?? ($doc['title'] ?? 'book').'.'.($message['audio'] ?? null ? 'mp3' : 'pdf'));
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (! in_array($ext, Library::EXTENSIONS, true)) {
                $telegram->sendMessage($chatId, 'نوع الملف غير مدعوم. الأنواع المقبولة: '.implode('، ', Library::EXTENSIONS), $messageId);

                return response()->json(['ok' => true]);
            }

            $unique = (string) ($doc['file_unique_id'] ?? '');
            if ($unique !== '' && ($existing = Book::query()->where('telegram_file_unique_id', $unique)->first())) {
                $telegram->sendMessage($chatId, "هذا الكتاب موجود مسبقاً: {$existing->title}", $messageId);

                return response()->json(['ok' => true]);
            }

            if (($doc['file_size'] ?? 0) > Telegram::MAX_DOWNLOAD) {
                $telegram->sendMessage($chatId, "حجم الملف أكبر من ٢٠ ميغا، وتيلغرام لا يسمح للبوتات بتنزيل ملفات بهذا الحجم.\nارفعه من لوحة التحكم: ".route('admin.books.create'), $messageId);

                return response()->json(['ok' => true]);
            }

            [$title, $author, $category] = array_pad(array_map('trim', explode('|', (string) ($message['caption'] ?? ''))), 3, null);

            $stream = $telegram->download($doc['file_id']);
            $book = $library->storeContents($stream, $name, [
                'title' => $title ? mb_substr(strtok($title, "\n"), 0, 180) : null,
                'author' => $author,
                'category' => $category,
                'source' => 'telegram',
                'telegram_file_unique_id' => $unique ?: null,
            ]);

            $telegram->sendMessage($chatId, "تمت إضافة «{$book->title}» إلى المكتبة ✅", $messageId);
        } catch (Throwable $e) {
            Log::error('Telegram webhook failed', ['error' => $e->getMessage()]);
            try {
                $telegram->sendMessage($chatId, 'حدث خطأ أثناء إضافة الكتاب، جرّب مرة أخرى أو ارفعه من لوحة التحكم.', $messageId);
            } catch (Throwable) {
            }
        }

        // Always 200 so Telegram doesn't retry the same update forever.
        return response()->json(['ok' => true]);
    }
}
