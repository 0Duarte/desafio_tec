<?php

namespace App\Repositories;

use App\Models\Transfer;
use App\Models\Wallet;

class TransferRepository
{
    public function initTransfer($payerId, $payeeId, $amount)
    {
        return Transfer::create([
            'payer_id' => $payerId,
            'payee_id' => $payeeId,
            'amount'   => $amount,
        ]);
   }

    public function finalizeTransfer(Transfer $transfer, $amount): void
    {
        $transfer->update(['amount' => $amount]);
    }

    public function updateTransferStatus(Transfer $transfer, string $status): void
    {
        $transfer->update(['status' => $status]);
    }
}