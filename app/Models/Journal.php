<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    protected $casts = ['quantity' => 'decimal:4'];

    public $timestamps = false;

    protected $fillable = [
        'quantity',
        'action',
        'code',
        'moreless',
        'user_id',
        'stock_id',
        'product_id',
        'fabrication',
        'validity',
        'batch',
        'warehouse',
        'hall',
        'position',
        'level',
        'immobilized',
        'immob_code',
    ];
}
