<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'balance',
    ];

    public function hasBalance(int $amount): bool
    {
        return $this->balance < $amount;
    }
}
