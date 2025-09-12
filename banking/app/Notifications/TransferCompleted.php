<?php

namespace App\Notifications;

use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferCompleted extends Notification  implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = 5;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [EmailChannel::class, SmsChannel::class];
    }
}
