<?php

namespace App\Services;

use App\DTO\TransferRequestDTO;
use App\Exceptions\MerchantCannotTransferException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\TransferCompleted;
use App\Repositories\TransferRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransferService
{
    private $walletRepository;
    private $transferRepository;
    private $authorizeExternalService;

    public function __construct(
        AuthorizationExternalService $authorizeExternalService,
        TransferRepository $transferRepository,
        WalletRepository $walletRepository
    ) {
        $this->authorizeExternalService = $authorizeExternalService;
        $this->transferRepository = $transferRepository;
        $this->walletRepository = $walletRepository;
    }

    /**
     * Do a transfer between two wallets
     *
     * @param TransferRequestDTO $requestDto
     * @return Transfer
     * @throws Exception
     */
    public function transfer(TransferRequestDTO $requestDto): Transfer
    {
        $amount = $requestDto->amountInCents();

        $transfer = $this->transferRepository->initTransfer(
            $requestDto->payerId,
            $requestDto->payeeId,
            $amount
        );

        try {
            DB::transaction(function () use ($amount, $requestDto, $transfer) {
                $payerWallet = $this->walletRepository->findByUserId($requestDto->payerId);
                $this->validateTransferRules($payerWallet, $amount);

                $payeeWallet = $this->walletRepository->findByUserId($requestDto->payeeId);
                $this->walletRepository->updateBalance($payerWallet, $payeeWallet, $amount);

                $this->authorizeExternalService->authorize();
                $this->transferRepository->finalizeTransfer($transfer, $amount);

                $this->notifyTransfer($payeeWallet->user);
            });
        } catch (Throwable $e) {
            $this->transferRepository->updateTransferStatus($transfer, Transfer::STATUS_FAILED);
            $this->logError($e, $transfer);
            throw $e;
        }

        return $transfer;
    }

    /**
     * Validate transfer rules
     * @param Wallet $payer
     * @param int $amount
     * @return void
     * @throws MerchantCannotTransferException
     * @throws InsufficientBalanceException
     */
    private function validateTransferRules(Wallet $payer, int $amount): void
    {
        if ($payer->isMerchant()) {
            throw new MerchantCannotTransferException();
        }

        if (!$payer->hasBalance($amount)) {
            throw new InsufficientBalanceException();
        }
    }

    /**
     * Notify user of completed transfer
     * @param User $user
     * @return void
     */
    private function notifyTransfer(User $user): void
    {
        $user->notify(new TransferCompleted());
    }

    /**
     * Log transfer errors
     * @param Throwable $exception
     * @param Transfer $transfer
     */
    private function logError(Throwable $exception, Transfer $transfer): void
    {
        Log::error('Transfer error', [
            'transfer_id' => $transfer->id,
            'amount' => $transfer->amount,
            'exception' => $exception->getMessage(),
        ]);
    }
}
