<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class reception extends Model
{
    protected $fillable = [
        'header',
        'product_id',
        'fabrication',
        'validity',
        'batch',
        'quantity',
        'user_id',
        'status',
    ];

    public function receptionHeader()
    {
        return $this->belongsTo(reception_header::class, 'header');
    }
}
