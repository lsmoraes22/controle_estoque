<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    protected $table = 'stock';
    protected $fillable = [
        'reception_id',
        'product_id',
        'supplier_id',
        'fabrication',
        'validity',
        'batch',
        'immobilized',
        'immob_code',
        'status',
        'quantity',
        'warehouse',
        'hall',
        'position',
        'level'
    ];

}
