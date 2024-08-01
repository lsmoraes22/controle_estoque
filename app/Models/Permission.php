<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['route'];

    public function sectors()
    {
        return $this->belongsToMany(Sector::class, 'sector_permissions')
                    ->withPivot('level', 'enabled')
                    ->withTimestamps();
    }
}
