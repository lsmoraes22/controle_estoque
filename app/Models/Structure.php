<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
    // id é a chave primária textual da model.
    // warehouse + hall + position + level formam a constraint UNIQUE da localização física.
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

    // Definindo o tipo da chave primária id como string
    protected $keyType = 'string';
    protected $casts = [
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
    public function countPositionStockFilled($warehouse, $hall, $position, $level){
        return Stock::where([
            'warehouse' => $warehouse,
            'hall' => $hall,
            'position' => $position,
            'level' => $level
        ])->count();
    }

    public function warehouseModel()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse', 'warehouse');
    }
}
