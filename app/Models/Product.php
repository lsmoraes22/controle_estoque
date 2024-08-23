<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
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
    protected $cast = [
        'enabled' => 'boolean',
    ];

    public function getTableFields()
    {
        return [
            'id' => 'id',
            'description' => 'description',
            'category_id' => 'category_id',
            'unit' => 'unit',
            'enabled' => 'enabled',
        ];
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

}
