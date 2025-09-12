<?php

namespace App\Models;

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
        return $this->balance < $amount;
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isCompany(): bool
    {
        return $this->user->type === 'company';
    }
}
