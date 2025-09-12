<?php

return [
    'external_service' => [
        'authorize' => env('EXTERNAL_SERVICE_AUTHORIZATION_URL', 'https://util.devi.tools/api/v2/authorize'),
        'notification' => env('EXTERNAL_SERVICE_NOTIFICATION_URL', 'https://util.devi.tools/api/v1/notify'),
    ],
];
