<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionParent extends Model
{
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'permission_parent_id');
    }
}
