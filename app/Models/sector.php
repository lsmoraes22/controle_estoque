<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Sector extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'sector',
        'enabled',
    ];

    protected $hidden = [
        'updated_at',
        'created_at'
    ];

    // Define o relacionamento com a model User
    public function users()
    {
        return $this->hasMany(User::class, 'sector_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'sector_permissions')
                    ->withPivot('level', 'enabled','read_write')
                    ->withTimestamps();
    }
}
