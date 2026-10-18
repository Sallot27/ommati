<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\Library;
use App\Services\Telegram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Receives updates from the Telegram bot.
 *
 * - Channel posts (bot is an admin in the channel): books are added silently,
 *   so nothing extra appears in the channel.
 * - Private chat: books sent or forwarded to the bot are added, with a reply.
 *
 * A chat must be approved once from the admin panel before it can add books.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, Telegram $telegram, Library $library)
    {
        if (! $telegram->enabled() || ! hash_equals($telegram->secret(), (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(403);
        }

        $isChannel = is_array($request->input('channel_post'));
        $message = $request->input('message') ?? $request->input('channel_post');
        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $chat = (array) ($message['chat'] ?? []);
        $chatId = (string) ($chat['id'] ?? '');
        $messageId = $message['message_id'] ?? null;
        $reply = function (string $text) use ($telegram, $chatId, $messageId, $isChannel) {
            if (! $isChannel) {
                $telegram->sendMessage($chatId, $text, $messageId);
            }
        };

        try {
            if (! in_array($chatId, $telegram->allowedChats(), true)) {
                $title = (string) ($chat['title'] ?? trim(($chat['first_name'] ?? '').' '.($chat['last_name'] ?? '')) ?: ($chat['username'] ?? $chatId));
                $telegram->rememberPending($chatId, $title, (string) ($chat['type'] ?? ''));
                $reply("أهلاً! لم يتم السماح لهذه المحادثة بعد.\nافتح لوحة التحكم ← الإعدادات، واضغط «سماح» بجانب «{$title}»، ثم أرسل الكتاب مرة أخرى.");

                return response()->json(['ok' => true]);
            }

            $text = trim((string) ($message['text'] ?? ''));
            if (! $isChannel && (str_starts_with($text, '/start') || str_starts_with($text, '/help'))) {
                $reply("أرسل أو حوّل لي أي كتاب (PDF أو EPUB) وأضيفه للمكتبة مباشرة.\nيمكنك تحويل عدة كتب دفعة واحدة.\nاكتب في وصف الملف (اختياري):\nالعنوان | المؤلف | التصنيف\n\n/list آخر الكتب المضافة");

                return response()->json(['ok' => true]);
            }
            if (! $isChannel && str_starts_with($text, '/list')) {
                $lines = Book::query()->latest()->limit(10)->get()->map(fn ($b, $i) => ($i + 1).'. '.$b->title)->implode("\n");
                $reply($lines ?: 'المكتبة فارغة حالياً.');

                return response()->json(['ok' => true]);
            }

            $doc = $message['document'] ?? $message['audio'] ?? null;
            if (! $doc) {
                if (! $isChannel && $text !== '') {
                    $reply('أرسل ملف الكتاب نفسه (PDF أو EPUB) وأنا أضيفه 📚');
                }

                return response()->json(['ok' => true]); // ordinary channel posts are ignored
            }

            $name = (string) ($doc['file_name'] ?? ($doc['title'] ?? 'book').'.'.(isset($message['audio']) ? 'mp3' : 'pdf'));
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (! in_array($ext, Library::EXTENSIONS, true)) {
                $reply('نوع الملف غير مدعوم. الأنواع المقبولة: '.implode('، ', Library::EXTENSIONS));

                return response()->json(['ok' => true]);
            }

            $unique = (string) ($doc['file_unique_id'] ?? '');
            if ($unique !== '' && ($existing = Book::query()->where('telegram_file_unique_id', $unique)->first())) {
                $reply("هذا الكتاب موجود مسبقاً: {$existing->title}");

                return response()->json(['ok' => true]);
            }

            if (($doc['file_size'] ?? 0) > Telegram::MAX_DOWNLOAD) {
                Log::info('Telegram book too large for the Bot API', ['name' => $name, 'size' => $doc['file_size']]);
                $reply("«{$name}» أكبر من ٢٠ ميغا، وتيلغرام لا يسمح للبوتات بتنزيل ملفات بهذا الحجم.\nارفعه من لوحة التحكم: ".route('admin.books.create'));

                return response()->json(['ok' => true]);
            }

            // Caption: "Title | Author | Category". In channels, only use it when it's a short single line.
            $caption = trim((string) ($message['caption'] ?? ''));
            if ($isChannel && (str_contains($caption, "\n") || mb_strlen($caption) > 150) && ! str_contains($caption, '|')) {
                $caption = '';
            }
            [$title, $author, $category] = array_pad(array_map('trim', explode('|', $caption)), 3, null);

            $stream = $telegram->download($doc['file_id']);
            $book = $library->storeContents($stream, $name, [
                'title' => $title ? mb_substr(strtok($title, "\n"), 0, 180) : null,
                'author' => $author,
                'category' => $category,
                'source' => 'telegram',
                'telegram_file_unique_id' => $unique ?: null,
            ]);

            $reply("تمت إضافة «{$book->title}» إلى المكتبة ✅");
        } catch (Throwable $e) {
            Log::error('Telegram webhook failed', ['error' => $e->getMessage()]);
            try {
                $reply('حدث خطأ أثناء إضافة الكتاب، جرّب مرة أخرى أو ارفعه من لوحة التحكم.');
            } catch (Throwable) {
            }
        }

        // Always 200 so Telegram doesn't retry the same update forever.
        return response()->json(['ok' => true]);
    }
}
