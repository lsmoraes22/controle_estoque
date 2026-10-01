<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable =[
        'id',
        'description',
        'sale_price',
        'purchase_price',
        'category_id',
        'unit',
        'unit_per_box',
        'box_ballast',
        'ballast_per_layer',
        'box_weight',
        'shelflife',
        'supplier_default_id',
        'abc_curve',
        'enabled'
    ];
    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected $hidden = [
        'updated_at',
        'created_at'
    ];

    public function getTableFields()
    {
        return [
            'id' => 'id',
            'description' => 'description',
            'category_id' => 'category',
            'unit' => 'unit',
            'enabled' => 'enabled',
        ];
    }

    public function getTypeDataFields($field){
        $data = [
            'id'     => 'integer',
            'category_id' => 'integer',
            'description'    => 'string',
            'unit' => 'string',
            'enabled'   => 'boolean',
            'sale_price' => 'integer',
            'purchase_price' => 'string',
            'category' => 'string',
            'unit_per_box' => 'string',
            'box_ballast' => 'string',
            'ballast_per_layer' => 'string',
            'box_weight' => 'string',
            'shelflife' => 'string',
            'supplier_default_id' => 'string',
            'abc_curve' => 'string'
        ];
        return $data[$field];
    }

    public function getForeignField(){
        return [
            'category_id' => [
                'table' => 'category', 
                'field' => 'category',
            ]
        ];
    }

        /**
     * Get the sector that owns the user.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function supplierProducts()
    {
        return $this->hasMany(SupplierProduct::class);
    }

}
