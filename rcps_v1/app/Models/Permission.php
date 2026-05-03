<?php

namespace App\Models;

class Permission extends \Spatie\Permission\Models\Permission
{
    public function parent()
    {
        return $this->belongsTo(PermissionParent::class, 'permission_parent_id');
    }
}
