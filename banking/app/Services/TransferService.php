<?php

namespace App\Services;

use App\Jobs\NotifyTransferJob;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class TransferService
{
    private $walletRepository;
    private $authorizeExternalService;
    
    public function __construct()
    {
        $this->authorizeExternalService = new AuthorizationExternalService();
        $this->walletRepository = new WalletRepository();
    }

    /**
     * Do a transfer between two wallets
     *
     * @param int $payerId
     * @param int $payeeId
     * @param float $amount
     * @return Transaction
     * @throws Exception
     */
    public function transfer(int $payerId, int $payeeId, float $amount): Transaction
    {
        $amountInCents = (int) round($amount * 100);

        if ($payerId === $payeeId) {
            throw new Exception('Payer and payee cannot be the same');
        }

        return DB::transaction(function () use ($amountInCents, $payerId, $payeeId) {

            $payer_wallet = $this->walletRepository->findByUserId($payerId); 

            $this->validateTransferRules($payer_wallet, $amountInCents);
            $this->authorizeExternalService->authorize();

            $payee_wallet = $this->walletRepository->findByUserId($payeeId);
 
            $this->walletRepository->updateBalance($payer_wallet, $payee_wallet, $amountInCents);

            $transaction = Transaction::create([
                'payer_id' => $payer_wallet->user_id,
                'payee_id' => $payee_wallet->user_id,
                'amount'   => $amountInCents,
                'status'   => 'completed',
            ]);

            dispatch(new NotifyTransferJob($transaction));

            return $transaction;
        });
    }

    private function validateTransferRules(Wallet $payer, int $amount): void
    {
        if ($payer->isCompany()) {
            throw new Exception("Lojistas não podem realizar transferências.");
        }

        if ($payer->hasBalance($amount)) {
            throw new Exception("Saldo insuficiente.");
        }
    }
}