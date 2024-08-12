<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'sector_id',
        'enabled',
        'password',
        'level'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'updated_at',
        'created_at'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'enabled' => 'boolean',
        ];
    }

    /**
     * Get the sector that owns the user.
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id', 'id');
    }
    public function getFields()
    {
        return $this->getFillable();
    }
    public function getTableFields()
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'enabled' => 'Enabled',
            'sector_id' => 'Sector',
            'level' => 'Level',
        ];
    }

    public function getForeignField(){
        return [
            'sector_id' => [
                'table' => 'sector', 
                'field' => 'sector',
            ]
        ];
    }

}
