<?php

namespace App\Traits;

use Illuminate\Support\Str;
use App\Models\RoleModel;

trait RoleAccess
{
    /**
     * The roles that belong to the user.
     */
    public function roles()
    {
        $roles = $this->belongsToMany(RoleModel::class, 'user_role_organizations');
        if (auth()->check()) {
            $roles = $roles->wherePivot('organization_id');
        }
        return $roles;
    }

    public function organizationroles()
    {
        return $this->belongsToMany(RoleModel::class, 'user_role_organizations', 'user_id', 'organization_id');
    }

    public function findUserRole($organization_id, $trim = true)
    {
        $find_role = $this->belongsToMany(RoleModel::class, 'user_role_organizations')->wherePivot('organization_id', $organization_id)->first();
        if ($trim) {
            return ucwords(Str::replace('_', ' ', $find_role->name));
        }
        return $find_role->name;
    }

    // public function hasAnyRole($roles)
    // {
    //     if (is_array($roles)) {
    //     foreach ($roles as $role) {
    //       if ($this->hasRole($role)) {
    //         return true;
    //       }
    //     }
    //   } else {
    //     if ($this->hasRole($roles)) {
    //       return true;
    //     }
    //   }
    //   return false;
    // }
    public function hasRole($role)
    {
      if ($this->roles->where('name', $role)->first()) {
        return true;
      }
      return false;
    }
    // /**
    //  * The roles that belong to the user.
    //  */
    // public function role()
    // {
    //     return $this->roles->first();
    // }

    public function assignRole($role, $organization, $user_id = null)
    {
        $user_id = $user_id ?? 1;
        $roles = RoleModel::where('name', $role)->first();
        return $this->roles()->attach($roles->id, ['organization_id' => $organization, 'user_id' => $user_id]);
    }

    public function removeOrg($organization)
    {
        $this->organizationroles()->detach(['organization_id' => $organization]);
    }
}
