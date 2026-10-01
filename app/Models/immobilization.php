<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class immobilization extends Model
{
    use HasFactory;

    protected $primaryKey = 'immob_code';
    public $incrementing = false;
    protected $keyType = 'string';
}
