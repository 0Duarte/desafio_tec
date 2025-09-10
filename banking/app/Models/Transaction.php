<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'amount',
        'type',
        'payer_id',
        'payee_id',
    ];

    public function payer()
    {
        return $this->belongsTo(Wallet::class, 'payer_id');
    }

    public function payee()
    {
        return $this->belongsTo(Wallet::class, 'payee_id');
    }
}
