<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class reception_header extends Model
{
    protected $fillable = [
        'supplier_id',
    ];

    public function receptions()
    {
        return $this->hasMany(Reception::class, 'header');
    }
}
