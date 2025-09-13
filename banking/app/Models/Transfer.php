<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    protected $fillable = [
        'amount',
        'type',
        'payer_id',
        'payee_id',
        'status',
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
