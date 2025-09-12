<?php

namespace App\Services;

use App\Exceptions\TransferAuthorizationException;
use Illuminate\Support\Facades\Http;

class AuthorizationExternalService
{
    public function authorize()
    {
        $response = Http::get('https://util.devi.tools/api/v2/authorize');

        if ($response->json('data.authorization')) {
            return true;
        } else {
            throw new TransferAuthorizationException();
        }
    }
}
