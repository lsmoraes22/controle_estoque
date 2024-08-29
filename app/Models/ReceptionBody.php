<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceptionBody extends Model
{
    protected $primaryKey = 'row';
    protected $table = 'reception_body';
    protected $fillable = [
        'header',
        'product_id',
        'fabrication',
        'validity',
        'batch',
        'quantity',
        'theoretical',
        'user_id',
        'stock_id',
        'structure_id',
        'received'
    ];

    protected $hidden = [
        'updated_at',
        'created_at'
    ];

    public function ReceptionHeader()
    {
        return $this->belongsTo(ReceptionHeader::class, 'header', 'id' );
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function Structure()
    {
        return $this->belongsTo(Structure::class, 'structure_id', 'id');
    }

    public function getTableFields()
    {
        return [
            'row' => 'Row',
            'header' => 'Header',
            'product_id' => 'Product ID',
            'product_description' => 'Product Description',
            'product_unit' => 'Product Unit',
            'received' => 'Received',
//            'fabrication' => 'Fabrication',
//            'validity' => 'Validity',
//            'batch' => 'Batch',
            'theoretical' => 'Theoretical',
//            'quantity' => 'Quantity',
//            'user_id'   => 'User ID',
//            'structure_id' => 'Structural ID',
//            'stock_id' => 'Stock ID',
        ];
    }
    public function getTypeDataFields($field){
        $data = [
            'id' => 'integer',
            'row' => 'integer',
            'header' => 'string',
            'product_id' => 'integer',
            'product_description' => 'string',
            'product_unit' => 'string',
            'product' => 'string',
            'fabrication' => 'date',
            'validity' => 'date',
            'batch' => 'string',
            'theoretical' => 'numeric',
            'quantity' => 'numeric',
            'user_id'   => 'integer',
            'structure_id' => 'integer',
            'stock_id' => 'integer',
            'received' => 'boolean'
        ];
        return $data[$field];
    }

    public function getForeignField(){
        return [
            'user_id' => [
                 'table' => 'user', 
                'field' => 'user',
            ],
            'structure_id' => [
                'table' => 'structure', 
                'field' => 'id',
            ],
            'product_description' => [
                'table' => 'product', 
                'field' => 'description',
            ],
            'product_unit' => [
                'table' => 'product', 
                'field' => 'unit',
            ]
        ];
    }
}
