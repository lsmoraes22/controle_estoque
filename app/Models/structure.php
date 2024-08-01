<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class structure extends Model
{
    // Definindo a chave primária composta
    protected $primaryKey = ['warehouse', 'hall', 'position', 'level'];
    
    // Desativando o auto-incremento
    public $incrementing = false;

    // Definindo o tipo das chaves primárias compostas como string
    protected $keyType = 'string';

}
