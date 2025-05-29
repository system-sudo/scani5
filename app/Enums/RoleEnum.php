<?php

namespace App\Enums;

enum RoleEnum
{
    public const SuperAdmin = 'sq1_super_admin';
    public const Admin = 'sq1_admin';
    public const User = 'sq1_user';
    public const OrgSuperAdmin = 'org_super_admin';
    public const OrgAdmin = 'org_admin';
    public const OrgUser = 'org_user';
}
