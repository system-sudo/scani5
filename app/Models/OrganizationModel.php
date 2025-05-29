<?php

namespace App\Models;

use App\Traits\{SearchableTrait,SortableTrait, FilterableTrait, LowercaseAttributes, PaginateResults};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationModel extends Model
{
    use HasFactory;
    use SearchableTrait;
    use SortableTrait;
    use FilterableTrait;
    use LowercaseAttributes;
    use PaginateResults;

    protected $table = 'organizations';

    protected $guarded = [];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'organization_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role_organizations', 'organization_id', 'user_id')->withPivot('role_id')
        ->withTimestamps();
    }

    public function roles()
    {
        return $this->hasOneThrough(RoleModel::class, UserRoleOrgModel::class, 'organization_id', 'id', 'id', 'role_id');
    }

    public function userRoleOrgs()
    {
        return $this->hasMany(UserRoleOrgModel::class, 'organization_id', 'id');
    }

    public function tickets()
    {
        return $this->hasOne(TicketingTool::class, 'organization_id', 'id');
    }

    public function otp()
    {
        return $this->hasOneThrough(TotpRegenerate::class, User::class, 'organization_id', 'user_id', 'id', 'id');
    }
}
