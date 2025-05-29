<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Helpers\LogHelper;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserRoleOrgModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\ResponseApi;

class UserController extends Controller
{
    use ResponseApi;

    //  get user api ---

    public function index(Request $request)
    {
        $search = request('search');
        $sort_column = request('sort_column');
        $sort_direction = request('sort_direction');
        $orgId = request('orgId') ?: Auth::user()->id;

       
        
        $accepted_sort_columns = ['name', 'email', 'status', 'organizations_count'];
        $filter = json_decode(request('filter')) ?? null;

        $role_filter = null;
        if ($filter && array_key_exists('role', (array) $filter)) {
            $role_filter = $filter->role;
            unset($filter->role);
        }

        try {
            $user = User::select('id', 'name', 'email', 'user_status as status', 'is_locked', 'created_at', 'mfa_token')
            ->withCount('organizations')
            ->with('otp')
            ->when($orgId, function ($query) use ($orgId) {
                if (!isSuperAdmin($orgId)) {
                    $query->whereHas('userRoleOrgs', function ($subquery) use ($orgId) {
                        $subquery->whereNotNull('organization_id')
                        ->where('organization_id', $orgId);
                    });
                    $query->whereHas('roles', function ($q) {
                        $q->whereNotIn('name', [RoleEnum::Admin, RoleEnum::User]);
                    });
                } else {
                    $query->whereHas('userRoleOrgs', function ($subquery) use ($orgId) {
                        $subquery->whereNull('organization_id')
                        ->whereIn('role_id', [RoleNameOrId(null, RoleEnum::SuperAdmin) ,RoleNameOrId(null, RoleEnum::Admin), RoleNameOrId(null, RoleEnum::User)])
                        ->whereNot('user_id', $orgId)
                        ;
                    });
                }
            })
            ->search($search, ['name', 'email', 'user_status'])
            ->when($filter, fn ($query) => $query->filter($filter))
            ->when($role_filter, function ($query) use ($role_filter) {
                $query->whereHas('roles', function ($q) use ($role_filter) {
                    $q->whereIn('name', $role_filter);
                });
            })
            // ->whereNot('id', getSuperAdminId())
            ->when(in_array($sort_column, $accepted_sort_columns), function ($query) use ($sort_column, $sort_direction) {
                $query->orderBy($sort_column, $sort_direction);
            })
            ->orderBy('created_at', 'desc')
            ->paginateresults();

            $user->getCollection()->transform(function ($item) {
                $item->reason = $item->otp ? $item->otp->reason : null;
                $item->role_name = $item->roles ? roleNameReadable($item->roles->pluck('name')->first()) : null;
                unset($item->organization, $item->otp);
                return $item;
            });

            $userRecords = UserResource::collection($user)->response()->getData(true);
            return $this->sendResponse($userRecords, 'Users displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function count(Request $request)
    {
        $orgId = request('orgId');

        $users = User::when($orgId, function ($query) use ($orgId) {
            if (!isSuperAdmin(Auth::user()->id)) {
                $query->whereHas('userRoleOrgs', function ($q) use ($orgId) {
                    $q->where('organization_id', $orgId);
                });
                $query->whereHas('roles', function ($q) {
                    $q->whereNotIn('name', [RoleEnum::Admin, RoleEnum::User]);
                });
            }
        })
        ->when(!$orgId, function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', [RoleEnum::Admin, RoleEnum::User]);
            });
        });

        try {
            $response = [
                'total' => $users->count(),
            ];

            return $this->sendResponse($response, 'User cards displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Fetch organization through user
     */
    public function organizationUsers($orgId, Request $request)
    {
        $search = request('search');
        $sort_column = request('sort_column');
        $sort_direction = request('sort_direction');
        $filter = json_decode(request('filter')) ?? null;

        $role_filter = null;
        if ($filter && array_key_exists('role', (array) $filter)) {
            $role_filter = $filter->role;
            unset($filter->role);
        }

        $accepted_sort_columns = ['name', 'email', 'status'];

        // $ignore_admin_rec = true;
        // if ($orgId == getSuperAdminId()) {
        //     $ignore_admin_rec = false;
        // }

        try {
            $user = User::select('id', 'name', 'email', 'user_status as status', 'is_locked', 'created_at')
               ->search($search, ['name', 'email', 'user_status'])
            //    ->with('roles', function ($role) use ($ignore_admin_rec) {
            //        if ($ignore_admin_rec) {
            //            $role->whereNotIn('name', [RoleEnum::Admin, RoleEnum::User]);
            //        }
            //    })
               ->whereHas('userRoleOrgs', fn ($query) => $query->where('organization_id', $orgId)->whereNotNull('organization_id'))
            //    ->when($ignore_admin_rec, fn ($q) => $q->whereNot('id', getSuperAdminId()))
               ->when($filter, fn ($qu) => $qu->filter($filter))
               ->when($role_filter, function ($query) use ($role_filter) {
                   $query->whereHas('roles', function ($q) use ($role_filter) {
                       $q->whereIn('name', $role_filter);
                   });
               })
               ->sort($sort_column, $sort_direction, $accepted_sort_columns)
               ->orderByDesc('created_at')
               ->paginateresults();

            $user->getCollection()->transform(function ($item) {
                $item->role_name = roleNameReadable($item->roles->pluck('name')->first());
                $item->reason = optional($item->otp)->reason;
 
                return $item;
            });

            $userRecords = UserResource::collection($user)->response()->getData(true);
            return $this->sendResponse($userRecords, 'Organization users displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function orguserCount(Request $request)
    {
        $orgId = request('orgId');

        if (!$orgId) {
            return $this->sendError('Organization is required');
        }

        // $ignore_admin_rec = true;
        // if ($orgId == getSuperAdminId()) {
        //     $ignore_admin_rec = false;
        // }

        $users = User::whereHas('userRoleOrgs', function ($query) use ($orgId) {
            $query->where('organization_id', $orgId);
        });
        // ->when($ignore_admin_rec, function ($q) {
        //     $q->whereNot('id', getSuperAdminId());
        // });

        try {
            
            $response = [
                'total' => $users->count(),
            ];

            return $this->sendResponse($response, 'User cards displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Fetch user profile
     */
    public function show($id)
    {
        $auth_id = auth()->user()->id;
        if ($auth_id != $id) {
            return $this->sendError('You are not authorized to perform this action.');
        }
        try {
            $user = User::select('name', 'email')->find($auth_id);

            return $this->sendResponse($user, 'User profile displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Profile update
     */
    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'name.required' => 'Name is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $auth_id = auth()->user()->id;

        if ($auth_id != $id && !isSuperAdmin($auth_id)) {
            return $this->sendError('You are not authorized to perform this action.');
        }

        try {
            $userData = User::find($id);

            User::where('id', $id)->update([
                'name' => $request->name,
            ]);
            // LogHelper::logAction('Updated', 'User', "User updated the '{$userData->name}'", getRoleId(), getSuperAdminOrgId());
            LogHelper::logAction('Updated', 'User', "User updated the '{$userData->name}'", getRoleId());
            return $this->sendResponse(null, 'Profile updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function unlock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ], [
            'user_id.required' => 'User is required.',
            'user_id.integer' => 'User must be an integer.',
            'user_id.exists' => 'User does not exist.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        DB::beginTransaction();

        try {
            $user = User::find($request->user_id);
            if (!$user->is_locked) {
                return $this->sendError('User is already unlocked');
            }

            $user->is_locked = 0; // Assuming 1 means "unlocked"
            $user->save();

            DB::table('password_attempts')->where('user_id', $user->id)->delete();
            DB::commit();
            LogHelper::logAction('Unlocked', 'User', "User unlocked the '{$user->name}'", getRoleId());

            return $this->sendResponse(null, 'User unlocked successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * password change
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'old_password' => 'required',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?
                ])[A-Za-z\d@$!%*?&]+$/',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value === $request->input('old_password')) {
                        $fail('The new password must be different from the old password.');
                    }
                }
                // 'prohibited:'.$request->old_password],
            ],

            'confirm_password' => 'required|same:new_password',
        ], [
            'user_id.required' => 'User is required.',
            'user_id.integer' => 'User must be an integer.',
            'user_id.exists' => 'User does not exist.',
            'old_password.required' => 'Old Password is required.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.string' => 'Password must be a valid string.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'confirm_password.required' => 'Confirm Password is required.',
            'confirm_password.same' => 'Password & Confirm Password do not match.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $auth_id = auth()->user()->id;

        if ($auth_id != $request->user_id) {
            return $this->sendError('You are not authorized to perform this action.');
        }

        try {
            $userData = User::find($auth_id);

            if (isSuperAdmin($auth_id)) {
                $orgId = $auth_id;
            } else {
                $role_check = UserRoleOrgModel::where('user_id', $auth_id)->first();
                $orgId = $role_check->organization_id;
            }

            if (Hash::check($request->old_password, Auth::user()->password)) {
                User::where('id', $auth_id)->update([
                    'password' => Hash::make($request->new_password),
                ]);

                $user = Auth::user();

                if ($user) {
                    $user->tokens()->delete();
                }

                LogHelper::logAction('Updated', 'User', "Password changed for '{$userData->name}'", getRoleId(), $orgId);

                return $this->sendResponse(null, 'Password changed Successfully.');
            } else {
                return $this->sendError('Incorrect old password');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Assign users to organization
     */
    public function assignOrganization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orgId' => 'required',
            'role_id' => 'required',
            'user_id' => 'required'
        ], [
            'user_id.required' => 'User is required.',
            'role_id.required' => 'Role is required.',
            'orgId.required' => 'Organization is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        try {
            $role_id = getRoleId();
            if (RoleNameOrId($role_id) != RoleEnum::Admin) {
                if (!postIsallowed($request->orgId)) {
                    return $this->sendError("Your role doesn't have permission to access this request", 403);
                }
            }

            $check = UserRoleOrgModel::where('organization_id', $request->orgId)
                ->where('user_id', $request->user_id)
                ->first();

            if ($check) {
                return $this->sendError('The user is already assigned to this organization');
            }

            $user_org = UserRoleOrgModel::create([
                'organization_id' => $request->orgId,
                'role_id' => $request->role_id,
                'user_id' => $request->user_id,
            ]);

            $user_name = User::find($request->user_id)->name;
            $org_name = getOrgName($request->orgId);

            LogHelper::logAction('Assigned', 'User', ($user_name) ? "The user '{$user_name}' assigned to '{$org_name}' organization" : "The user assigned to '{$org_name}' organization", $role_id, $request->orgId);

            return $this->sendResponse(null, "User assigned to '{$org_name}' organization succesfully");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Remove user from organization
     */
    public function removeUserOrganization($orgId, $userId)
    {
        $curr_user_id = Auth::user()->id;

        $user_name = User::find($userId)->name;

        $org_name = getOrgName($orgId);

        if ($curr_user_id == $userId) {
            return $this->sendError('You cannot delete yourself');
        }

        DB::beginTransaction();

        try {
            $rec_check = UserRoleOrgModel::where('user_id', $userId)->get();
            
            $rec = UserRoleOrgModel::where('organization_id', $orgId)
                ->where('user_id', $userId)
                ->first();

            if (in_array($rec->roles->name, [RoleEnum::Admin, RoleEnum::User])) {
                DB::transaction(function () use ($userId) {
                    UserRoleOrgModel::where('user_id', $userId)->delete();
                    User::find($userId)->forceDelete();
                });
            } else {
                $rec->delete();
            }

            DB::commit();

            LogHelper::logAction('Unassigned', 'User', ($org_name) ? "User removed this '{$user_name}' user from '{$org_name}' organization" : "User removed some user from '{$org_name}' organization", getRoleId(), $orgId);

            return $this->sendResponse(null, 'User deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Delete organization user
     */
    public function deleteOrganizationUser($orgId, $user_id)
    {
        try {
            $check_user_role = UserRoleOrgModel::where('user_id', $user_id)
                ->where('organization_id', $orgId)->first();

            if ($check_user_role->roles->name === RoleEnum::SuperAdmin) {
                return $this->sendError('Super admin cannot be deleted');
            }

            if ($user_id == Auth::user()->id) {
                return $this->sendError('You cannot delete yourself');
            }

            $user = User::find($user_id);

            DB::transaction(function () use ($orgId, $user_id, $user) {
                $rec = UserRoleOrgModel::where('user_id', $user_id)->get();

                // Delete the user's role in the specified organization
                UserRoleOrgModel::where('user_id', $user_id)
                    ->where('organization_id', $orgId)
                    ->delete();

                // If the user has no other roles, delete the user
                if ($rec->count() == 1) {
                    $user->forceDelete();
                }
            });

            LogHelper::logAction('Deleted', 'User', "User deleted the '{$user->name}'", getRoleId(), $orgId);

            return $this->sendResponse(null, 'User deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Delete
     */
    public function destroy($orgId, $userId)
    {
        if (Auth::user()->id == $userId) {
            return $this->sendError('You cannot delete yourself');
        }
        try {
            $check_role = User::find($userId);
            $current_role_id = Auth::user()->roles->first()->id ?? null;
            if ($check_role && $check_role->roles->pluck('name')->intersect([RoleEnum::SuperAdmin])->isNotEmpty()) {
                return $this->sendError('Super admin cannot be deleted');
            }

            $check_role->forceDelete();
            LogHelper::logAction('Deleted', 'Organization user', "Delete organization's user with email of {$check_role->email}", $current_role_id, $orgId);
            return $this->sendResponse(null, 'User deleted successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('User not found', null, 404);
        }
    }
}
