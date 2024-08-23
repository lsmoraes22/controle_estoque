<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class reception_body extends Model
{
    protected $table = 'reception_body';
    protected $fillable = [
        'header',
        'product_id',
        'fabrication',
        'validity',
        'batch',
        'theoretical',
        'quantity',
        'user_id'
    ];

    public function reception_header()
    {
        return $this->belongsTo(reception_header::class, 'header');
    }
}
