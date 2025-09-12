<?php

namespace App\Repositories;

use App\Models\Wallet;

class WalletRepository
{
    public function findByUserId($userId)
    {
        return Wallet::where('user_id', $userId)->first();
    }

    public function updateBalance($payerWallet, $payeeWallet, $amount):void
    {
        $payerWallet->decrement('balance', $amount);
        $payeeWallet->increment('balance', $amount);
    }
}