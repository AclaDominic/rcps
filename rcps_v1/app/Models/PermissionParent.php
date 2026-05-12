<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionParent extends Model
{
    protected $fillable = ['name', 'is_active'];

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'permission_parent_id');
    }
}
