<?php

namespace App\Notifications\Channels;

use App\Notifications\TransferCompleted;
use App\Services\NotificationExternalService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    protected $notificationService;
    
    public function __construct(NotificationExternalService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    public function send($notifiable)
    {
        try {
            $this->notificationService->notify();
            Log::info('Successfully sent SMS to ' . $notifiable->name);
        } catch (\Exception $e) {
            Log::error('Error sending SMS to ' . $notifiable->name . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
