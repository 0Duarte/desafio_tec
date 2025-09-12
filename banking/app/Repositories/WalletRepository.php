<?php

namespace App\Repositories;

use App\Models\Wallet;

class WalletRepository
{
    public function findByUserId($userId)
    {
        return Wallet::where('user_id', $userId)->first();
    }

    public function updateBalance($payer_wallet, $payee_wallet, $amount):void
    {
        $payer_wallet->decrement('balance', $amount);
        $payee_wallet->increment('balance', $amount);
    }
}