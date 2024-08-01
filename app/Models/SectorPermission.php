<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SectorPermission extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'sector_id',
        'permission_id',
        'level',
        'enabled',
        'read_write',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected $hidden = [
        'updated_at',
        'created_at'
    ];

    public function sector(){
        return $this->belongsTo(Sector::class, 'sector_id', 'id');
    }

    public function permission() {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
    }
}
