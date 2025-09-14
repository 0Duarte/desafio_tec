<?php

namespace App\DTO;

class TransferRequestDTO
{
    public function __construct(
        public readonly int $payerId,
        public readonly int $payeeId,
        public readonly float $amount
    ) {}
    
    public function amountInCents(): int
    {
        return (int) round($this->amount * 100);
    }
}