<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class Reminder extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public string $url,
        public string $tag = 'reminder',
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon(asset('icons/icon-192.png'))
            ->badge(asset('icons/badge-72.png'))
            ->tag($this->tag)
            ->lang('ar')
            ->dir('rtl')
            ->data(['url' => $this->url])
            ->options(['TTL' => 3600, 'urgency' => 'high']);
    }
}
