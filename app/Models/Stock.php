<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $casts = ['quantity' => 'decimal:4'];

    protected $table = 'stock';

    protected $fillable = [
        'reception_id',
        'product_id',
        'supplier_id',
        'fabrication',
        'validity',
        'batch',
        'immobilized',
        'immob_code',
        'status',
        'quantity',
        'warehouse',
        'hall',
        'position',
        'level',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function receptionBody()
    {
        return $this->belongsTo(ReceptionBody::class, 'reception_id', 'row');
    }
}
