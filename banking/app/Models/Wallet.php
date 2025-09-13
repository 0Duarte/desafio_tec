<?php

namespace App\Models;

use App\Enums\UserTypeEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * @property \App\Models\User $user
 */
class Wallet extends Model
{
    protected $fillable = [
        'balance',
    ];

    public function hasBalance(int $amount): bool
    {
        return $this->balance >= $amount;
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isMerchant(): bool
    {
    return $this->user->type === UserTypeEnum::MERCHANT->value;
    }
}
