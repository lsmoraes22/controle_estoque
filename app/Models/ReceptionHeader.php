<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceptionHeader extends Model
{
    protected $fillable = [
        'supplier_id',
        'xml_nf_header_id',
        'status',
        'enabled',
        'rows'
    ];

    protected $hidden = [
        'updated_at',
        'created_at'
    ];

    public $casts = [
        'enabled' => 'boolean',
    ];

    public function ReceptionBody()
    {
        return $this->hasMany(ReceptionBody::class, 'header');
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function getTableFields()
    {
        return [
            'id' => 'ID',
            'supplier_id' => 'Supplier',
            'xml_nf_header_id' => 'XML NF ID',
            'status' => 'Status',
            'enabled' => 'Enabled'
        ];
    }
    public function getTypeDataFields($field){
        $data = [
            'id' => 'integer',
            'supplier_id' => 'string',
            'supplier' => 'string',
            'xml_nf_header_id' => 'string',
            'status' => 'string',
            'enabled' => 'boolean',
            'rows' => 'integer',
        ];
        return $data[$field];
    }

    public function getForeignField(){
        return [
            'supplier_id' => [
                'table' => 'supplier', 
                'field' => 'supplier',
            ],
        ];
    }
}
