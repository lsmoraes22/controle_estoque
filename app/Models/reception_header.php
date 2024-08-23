<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class reception_header extends Model
{
    protected $fillable = [
        'supplier_id',
        'xml_nf_header_id',
        'status'
    ];

    public function reception_body()
    {
        return $this->hasMany(reception_body::class, 'header');
    }
}
