<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier',
        'address',
        'phone1',
        'phone2',
        'phone3',
        'email',
        'cnpj',
        'ie',
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
            'supplier'  => 'Supplier',
            //'address'   => 'Adress',
            //'phone1'    => 'Phone1',
            //'phone2'    => 'Phone2',
            //'phone3'    => 'Phone3',
            //'email'     => 'Email',
            'cnpj'      => 'CNPJ',
            //'ie'        => 'IE',
            'enabled'   => 'Enabled',
        ];
    }

    public function getForeignField(){
        return [];
    }
}
