<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Minimal Telegram Bot API client. */
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
