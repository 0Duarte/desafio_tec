<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
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
