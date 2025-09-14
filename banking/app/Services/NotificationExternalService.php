<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class NotificationExternalService
{
    public function notify()
    {
        $response = Http::post(config('transfer.external_service.notification'));

        if ($response->successful()) {
            return true;
        } else {
            throw new Exception('Failed to access external notification service');
        }
    }
}
