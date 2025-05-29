<?php

use App\Enums\RoleEnum;
use App\Models\RoleModel;
use App\Models\UserRoleOrgModel;
use App\Models\User;
use App\Models\OrganizationModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

function folderFindOrCreate($id)
{
    $orgData = OrganizationModel::find($id);

    if ($orgData->folder_path != null) {
        return $orgData->folder_path;
    } else {
        // Construct the organization folder path
        $path = 'organizations/org_' . $id;

        // Check if the folder exists
        if (!Storage::disk('public')->exists($path)) {
            // If not, create it
            Storage::disk('public')->makeDirectory($path);
            $orgData->folder_path = $path;
            $orgData->save();
        } else {
            $orgData->folder_path = $path;
            $orgData->save();
        }

        return $path;
    }
}

function createReportFolder($org_path)
{
    $path = $org_path . '/reports';

    if (!Storage::disk('public')->exists($path)) {
        // If not, create it
        Storage::disk('public')->makeDirectory($path);
    }

    return $path;
}

function getRoleId()
{
    // returns first row of role id
    return Auth::user()->roles->value('id');
}

function getOrgEmail($orgId)
{
    $org = OrganizationModel::with(['users' => function ($q) {
        $q->where('role_id', 4)
          ->whereNot('user_id', 1);
    }])->find($orgId);

    return $org->users()->value('email');
}

function getOrgName($orgId)
{
    return OrganizationModel::where('id', $orgId)->value('name');
}

function exportFileName($orgId, $module, $ext)
{
    $orgName = Str::snake(getOrgName($orgId));

    $fileName = "scani5_{$orgName}_{$module}_export.{$ext}";

    return $fileName;
}

function RoleNameOrId($id = null, $name = null)
{
    if ($id) {
        return RoleModel::find($id)?->name;
    }

    if ($name) {
        return RoleModel::where('name', $name)->value('id');
    }

    return null;
}

function deleteExportFolder()
{
    $path = 'exports';

    Storage::disk('public')->deleteDirectory($path);

    return ['status' => 'success', 'message' => 'Export folder deleted successfully'];
}

function resetExportFolder()
{
    $path = public_path('exports');

    File::deleteDirectory($path);

    File::makeDirectory($path, 0777, true, true);
    return true;
}

function createExportFolder()
{
    $path = 'exports';

    if (!Storage::disk('public')->exists($path)) {
        Storage::disk('public')->makeDirectory($path);
    }

    return $path;
}

function getLogo($id)
{
    $org = OrganizationModel::find($id);
    if ($org->dark_logo != null) {
        $org->dark_logo = asset(Storage::url($org->folder_path . '/' . $org->dark_logo));
    }

    return $org;
}

function isSuperAdmin($id)
{
    return User::find($id)?->roles->contains('name', RoleEnum::SuperAdmin) ?? false;
}

function postIsallowed($orgId)
{
    $user_id = auth()->user()->id;
    $isAdmin = isSuperAdmin($user_id);

    if ($isAdmin) {
        return true;
    }

    $dataval = UserRoleOrgModel::where('user_id', auth()->user()->id)->where('organization_id', $orgId)->count();

    if (!$dataval) {
        return false;
    }

    return true;
}

function other_logout($id)
{
    $user = User::find($id);

    if ($user) {
        $tokens = $user->tokens;
        foreach ($tokens as $token) {
            $token->delete();
        }
        return true;
    }

    return false;
}

function ordinal($number)
{
    $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
        return $number . 'th';
    }

    return $number . $ends[$number % 10];
}

function isOrgActive($id)
{
    $orgStatus = OrganizationModel::where('id', $id)->value('status');
    $result = ($orgStatus == 'active') ? true : false;

    return $result;
}

function roleNameReadable($name)
{
    return str_replace('_', ' ', $name);
}



// function isSuperAdminOrg($orgId)
// {
//     return UserRoleOrgModel::where('organization_id', $orgId)
//         ->first()?->roles?->name === RoleEnum::SuperAdmin ?? false;
// }

// function getSuperAdminId()
// {
//     return User::whereHas('roles', function ($q) {
//         $q->where('name', RoleEnum::SuperAdmin);
//     })->value('id');
// }

// function getSuperAdminOrgId()
// {
//     return OrganizationModel::whereHas('roles', function ($q) {
//         $q->where('name', RoleEnum::SuperAdmin);
//     })->value('id');
// }

function getRiskStatus($value = '')
{
    $rangesJson = config('custom.risk_ranges');
    $ranges = json_decode($rangesJson, true);

    // Convert all keys to lowercase
    $ranges = array_change_key_case($ranges, CASE_LOWER);

    if ($value) { // To get the ranges
        foreach ($ranges as $key => $range) {
            if ($value >= $range['min'] && $value <= $range['max']) {
                return $key;
            }
        }
        return 'unknown';
    } elseif ($value == 0) {
        return '';
    } else { //To get the risk levels array
        return array_map(fn ($range) => array_values($range), $ranges);
    }
}

function getRolePrevilage($role_id)
{
    $userRoles = Auth::user()->roles->pluck('name');

    if ($userRoles->contains(RoleEnum::SuperAdmin)) {
        return true;
    }

    $adminRoles = [
        RoleEnum::Admin => [3, 5, 6],
        RoleEnum::OrgSuperAdmin => [5, 6],
        RoleEnum::OrgAdmin => [6]
    ];

    foreach ($adminRoles as $role => $allowedRoles) {
        if (in_array($role_id, $allowedRoles) && $userRoles->contains($role)) {
            return true;
        }
    }

    return false;
}


function allAdminRoles($user_id){
    $user = User::find($user_id);
    $roles = $user->roles->pluck('name');

    return $roles->contains(RoleEnum::SuperAdmin) ||
           $roles->contains(RoleEnum::Admin) ||
           $roles->contains(RoleEnum::User);
}

function adminAndUserRoles($user_id){
    $user = User::find($user_id);
    $roles = $user->roles->pluck('name');

    return $roles->contains(RoleEnum::Admin) ||
           $roles->contains(RoleEnum::User);
}



function calculateAge($firstSeen)
{
    if (!$firstSeen) {
        return '';
    }
    $diff = Carbon::parse($firstSeen)->diff(Carbon::now());
    $parts = [];

    if ($diff->y) {
        $parts[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
    }
    if ($diff->m) {
        $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
    }
    if ($diff->d) {
        $parts[] = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
    }

    return implode(' ', $parts);
}
