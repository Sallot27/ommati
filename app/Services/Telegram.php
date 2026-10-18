<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Minimal Telegram Bot API client, plus which chats may add books. */
class Telegram
{
    /** Bot API refuses downloads over 20 MB. */
    public const MAX_DOWNLOAD = 20 * 1024 * 1024;

    public function token(): ?string
    {
        return config('services.telegram.bot_token');
    }

    public function enabled(): bool
    {
        return (bool) $this->token();
    }

    /** Webhook secret: TELEGRAM_WEBHOOK_SECRET, or derived from APP_KEY so no extra setup is needed. */
    public function secret(): string
    {
        $set = (string) config('services.telegram.webhook_secret');

        return $set !== '' ? $set : substr(hash('sha256', 'telegram-webhook|'.config('app.key')), 0, 48);
    }

    public function setWebhook(string $url): void
    {
        $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $this->secret(),
            'allowed_updates' => ['message', 'channel_post'],
        ]);
    }

    /** @return list<string> chat ids allowed to add books (from env + approved in admin) */
    public function allowedChats(): array
    {
        $approved = array_keys((array) json_decode((string) Setting::get('telegram_chats'), true));

        return array_values(array_unique(array_map('strval', array_merge(config('services.telegram.allowed_chat_ids', []), $approved))));
    }

    /** @return array<string, array{title: string, type: string}> */
    public function approvedChats(): array
    {
        return (array) json_decode((string) Setting::get('telegram_chats'), true);
    }

    /** @return array<string, array{title: string, type: string, at: string}> chats that wrote to the bot but are not approved */
    public function pendingChats(): array
    {
        return array_diff_key((array) json_decode((string) Setting::get('telegram_pending'), true), array_flip($this->allowedChats()));
    }

    public function rememberPending(string $chatId, string $title, string $type): void
    {
        $pending = (array) json_decode((string) Setting::get('telegram_pending'), true);
        $pending[$chatId] = ['title' => $title, 'type' => $type, 'at' => now()->toDateTimeString()];
        Setting::put('telegram_pending', json_encode(array_slice($pending, -10, null, true), JSON_UNESCAPED_UNICODE));
    }

    public function approve(string $chatId): void
    {
        $pending = (array) json_decode((string) Setting::get('telegram_pending'), true);
        $approved = $this->approvedChats();
        $approved[$chatId] = ['title' => $pending[$chatId]['title'] ?? $chatId, 'type' => $pending[$chatId]['type'] ?? ''];
        unset($pending[$chatId]);
        Setting::putMany([
            'telegram_chats' => json_encode($approved, JSON_UNESCAPED_UNICODE),
            'telegram_pending' => json_encode($pending, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function revoke(string $chatId): void
    {
        $approved = $this->approvedChats();
        unset($approved[$chatId]);
        Setting::put('telegram_chats', json_encode($approved, JSON_UNESCAPED_UNICODE));
    }

    public function call(string $method, array $params = []): array
    {
        $res = Http::timeout(30)->asJson()->post("https://api.telegram.org/bot{$this->token()}/{$method}", $params);
        if (! $res->json('ok')) {
            throw new RuntimeException('Telegram '.$method.' failed: '.$res->body());
        }

        return (array) $res->json('result');
    }

    public function sendMessage(int|string $chatId, string $text, ?int $replyTo = null): void
    {
        $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_to_message_id' => $replyTo,
        ]));
    }

    /** @return resource */
    public function download(string $fileId)
    {
        $file = $this->call('getFile', ['file_id' => $fileId]);
        $url = "https://api.telegram.org/file/bot{$this->token()}/{$file['file_path']}";
        $tmp = tmpfile();
        $res = Http::timeout(300)->withOptions(['sink' => $tmp])->get($url);
        if (! $res->successful()) {
            throw new RuntimeException('Telegram file download failed.');
        }
        rewind($tmp);

        return $tmp;
    }
}
