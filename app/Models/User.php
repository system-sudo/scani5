<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\RoleAccess;
use App\Traits\SearchableTrait;
use App\Traits\SortableTrait;
use App\Traits\FilterableTrait;
use App\Traits\LowercaseAttributes;
use App\Traits\PaginateResults;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use SearchableTrait;
    use SortableTrait;
    use RoleAccess;
    use FilterableTrait;
    use LowercaseAttributes;
    use PaginateResults;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_status',
        'is_locked'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_token'
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
        ];
    }

    public function createCustomToken($appName, $status = 'registered')
    {
        $scope = $status === 'verified' ? '2fa_status_verified' : '2fa_status_registered';

        return $this->createToken($appName, [$scope]);
    }

    public function hasActiveToken()
    {
        return $this->tokens()->where('revoked', false)->where('expires_at', '>', now())->exists();
    }

    public function otp()
    {
        return $this->hasOne(TotpRegenerate::class);
    }

    public function organizations()
    {
        return $this->belongsToMany(OrganizationModel::class, UserRoleOrgModel::class, 'user_id', 'organization_id');
    }

    public function roles()
    {
        return $this->belongsToMany(RoleModel::class, 'user_role_organizations', 'user_id', 'role_id')->withPivot('role_id');
    }

    public function userRoleOrgs()
    {
        return $this->hasMany(UserRoleOrgModel::class, 'user_id', 'id');
    }

    public function hasAnyRole($roles)
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }
}
