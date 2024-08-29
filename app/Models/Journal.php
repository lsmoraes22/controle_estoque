<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
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
        'immob_code'
    ];
}
