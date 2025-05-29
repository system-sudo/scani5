<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\{PaginateResults};

class UserRoleOrgModel extends Model
{
    use HasFactory;
    use PaginateResults;
    protected $table = 'user_role_organizations';

    protected $guarded = [];

    public function users()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function roles()
    {
        return $this->hasOne(RoleModel::class, 'id', 'role_id');
    }

    public function organization()
    {
        return $this->hasOne(OrganizationModel::class, 'id', 'organization_id');
    }
}
