<?php

namespace App\Models;

use App\Enums\UserTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property \App\Models\User $user
 */
class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'balance',
    ];

    public function transfers()
    {
        return $this->hasMany(Transfer::class, 'payer_id');
    }

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
