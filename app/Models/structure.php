<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class structure extends Model
{
    // Definindo a chave primária composta
    protected $primaryKey = 'id';
    
    // Desativando o auto-incremento
    public $incrementing = false;

    protected $fillable = [
        'id',
        'warehouse',
        'hall',
        'position',
        'level',
        'type',
        'filled',
        'immobilized',
        'enabled'
    ];

        /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'updated_at',
        'created_at'
    ];

    // Definindo o tipo das chaves primárias compostas como string
    protected $keyType = 'string';
    protected $cast = [
        'filled' => 'boolean',
        'immobilized' => 'boolean',
        'enabled' => 'boolean',
    ];

    /**
     * Summary of getFields
     * @return string[]
     */
    public function getFields()
    {
        return $this->getFillable();
    }
    /**
     * Summary of get
     * @return []
     */
    public function getTableFields()
    {
        return [
            'id' => 'ID',
            'warehouse' => 'Warehouse',
            'hall' => 'Hall',
            'position' => 'Position',
            'level' => 'Level',
            'immobilized' => 'Immobilized',
            'filled' => 'Filled',
            'enabled' => 'Enabled'
        ];
    }

    public function getTypeDataFields($field){
        $data = [
            'id'            => 'string',
            'warehouse'     => 'string',
            'hall'          => 'integer',
            'position'      => 'integer',
            'level'         => 'integer',
            'immobilized'   => 'boolean',
            'filled'        => 'boolean',
            'enabled'       => 'boolean'
        ];
        return $data[$field];
    }

    public function getForeignField(){
        return [];
    }
}
