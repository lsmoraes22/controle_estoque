<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class warehouse extends Model
{
    use HasFactory;

    protected $primaryKey = 'warehouse';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'warehouse',
        'description',
        'multiple',
        'weight_max_alveolus',
        'type',
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

    protected $cast = [
        'enabled' => 'boolean',
        'multiple' => 'boolean',
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
            'warehouse' => 'Warehouse',
            'description' => 'Description',
            'multiple' => 'multiple',
            'weight_max_alveolus' => 'Weight Max Alveolus',
            'type' => 'Type',
            'enabled' => 'Enabled'
        ];
    }

    public function getTypeDataFields($field){
        $data = [
            'warehouse' => 'string',
            'hall' => 'string',
            'position' => 'string',
            'level' => 'string',
            'multiple' => 'boolean',
            'weight_max_alveolus' => 'integer',
            'enabled' => 'boolean'
        ];
        return $data[$field];
    }

    public function getForeignField(){
        return [];
    }
}
