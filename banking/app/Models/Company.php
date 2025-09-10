<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'email',
        'document_type',
        'document_number',
    ];

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'user');
    }
}
