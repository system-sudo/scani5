<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Helpers\LogHelper;
use App\Http\Resources\CommonResource;
use App\Http\Resources\OrgResource;
use App\Models\OrganizationModel;
use App\Models\PasswordReset;
use App\Models\RoleModel;
use App\Models\TicketingTool;
use App\Models\User;
use App\Models\UserRoleOrgModel;
use App\Rules\ValidEmail;
use App\Rules\Recaptcha;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Str;
use App\Notifications\CustomNotification;
use App\Notifications\ResendInvite;
use Illuminate\Support\Facades\Storage;
use App\ResponseApi;
use App\Services\TrelloService;
use App\Services\FreshService;
use App\Services\JiraService;

class OrganizationController extends Controller
{
    use ResponseApi;
    protected $trelloService;
    protected $jiraService;
    protected $freshService;

    public function __construct(TrelloService $trelloService, FreshService $freshService, JiraService $jiraService)
    {
        $this->trelloService = $trelloService;
        $this->jiraService = $jiraService;
        $this->freshService = $freshService;
    }

    public function index(Request $request)
    {
        $search = request('search');
        $sort_column = request('sort_column');
        $sort_direction = request('sort_direction');

        $filter = json_decode(request('filter')) ?? null;

        $accepted_sort_columns = ['name', 'status', 'email'];

        try {
            $organization = OrganizationModel::select('organizations.id', 'organizations.name', 'organizations.status', DB::raw('GROUP_CONCAT(totp.reason) as reason'), DB::raw('ANY_VALUE(users.email) as email'))
              ->join('user_role_organizations as uro', 'organizations.id', '=', 'uro.organization_id')
              ->leftJoin('totp_regeneration as totp', 'uro.user_id', '=', 'totp.user_id')
              ->join('users', function ($join) {
                  $join->on('uro.user_id', '=', 'users.id')
                  ->where('uro.role_id', RoleNameOrId(null, RoleEnum::OrgSuperAdmin));
              })
              ->with(['users:users.email'])
              ->when($search, function ($s) use ($search) {
                  $s->search($search, ['organizations.name', 'organizations.status', 'organizations.short_name']);
                  $s->orwhereHas('users', function ($qs) use ($search) {
                      $qs->where('email', 'LIKE', '%' . trim($search) . '%');
                      $qs->where('role_id', RoleNameOrId(null, RoleEnum::OrgSuperAdmin));
                  });
              })
              ->when($filter, fn ($query) => $query->filter($filter))
              ->when(in_array($sort_column, $accepted_sort_columns), function ($query) use ($sort_column, $sort_direction) {
                  $query->orderBy($sort_column, $sort_direction ?? 'asc');
              }, function ($query) {
                  $query->orderBy('organizations.created_at', 'desc');
              })
              ->groupBy('organizations.id')
              ->paginateresults();

            $organizations = OrgResource::collection($organization)->response()->getData(true);

            return $this->sendResponse($organizations, 'Organization displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function count(Request $request)
    {
        try {
            $org = OrganizationModel::
            // whereNot('id', getsuperAdminId())
            get();

            $active = (clone $org)->where('status', 'active')->count();
            $inactive = (clone $org)->where('status', 'inactive')->count();

            $response = [
                'total' => $org->count(),
                'active' => $active,
                'inactive' => $inactive
            ];

            return $this->sendResponse($response, 'Organization cards displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function logo(Request $request)
    {
        $org_id = request('orgId');

        if (!$org_id) {
            return $this->sendError('Organization is mandatory');
        }

        $org = OrganizationModel::select('dark_logo', 'folder_path', 'updated_at')
            ->whereId($org_id)
            ->first();

        if ($org->dark_logo) {
            $imagePath = $org->folder_path . '/' . $org->dark_logo;

            if (Storage::disk('public')->exists($imagePath)) {
                // Add timestamp as query parameter to bust cache
                $timestamp = $org->updated_at->timestamp;
                $imageUrl = Storage::disk('public')->url($imagePath) . '?v=' . $timestamp;
                return $this->sendResponse(['image_url' => $imageUrl], 'Success');
            }
            return $this->sendError('Image file not found');
        }

        return $this->sendError('No logo found for this organization');
    }

    /**
     * User Organizations organization  controller
     */
    public function userOrganizations($user_id, Request $request)
    {

        $search = request('search') ? trim(request('search')) : null;
        $sort_column = request('sort_column');
        $sortableColumns = [
            'role' => 'roles.name',
            'organization_name' => 'organizations.name',
        ];
        $sortColumn = $sortableColumns[$sort_column] ?? 'organizations.name';
        $sortDirection = in_array(strtolower(request('sort_direction')), ['asc', 'desc']) ? request('sort_direction') : 'asc';

        
        try {
             $organization = UserRoleOrgModel::select('user_role_organizations.role_id', 'user_role_organizations.organization_id')
            ->leftJoin('organizations', 'organizations.id', '=', 'user_role_organizations.organization_id')
            ->leftJoin('roles', 'roles.id', '=', 'user_role_organizations.role_id')
            ->where('user_role_organizations.user_id', $user_id)

            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('organizations.name', 'like', '%' . $search . '%')
                        ->orWhere('roles.name', 'like', '%' . $search . '%');
                });
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginateresults();
            $organization->makeHidden('role_id');

            $organization->getCollection()->transform(function ($item) {
                $item->id = $item->organization_id ??  NULL;
                $item->name = $item->organization->name ??  config('custom.admin_organization');
                $item->role = roleNameReadable($item->roles->name);
                unset($item->organization, $item->roles, $item->organization_id);
                return $item;
            });

            $orgRecords = CommonResource::collection($organization)->response()->getData(true);

            return $this->sendResponse($orgRecords, 'organization displayed successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * user through organization cards
     */
    public function userOrganizationsCount(Request $request)
    {
        $user_id = request('userId');

        if (!$user_id) {
            return $this->sendError('User is required');
        }

        $user_value = User::find($user_id);
        $user = ($user_value->name) ? $user_value->name : $user_value->email;
        try {
            $org = UserRoleOrgModel::where('user_id', $user_id);

            $active = (clone $org)->whereHas('organization', function ($sq) {
                $sq->where('status', 'active');
            })->count();
            $inactive = (clone $org)->whereHas('organization', function ($sq) {
                $sq->where('status', 'inactive');
            })->count();

            $response = [
                'total' => $org->count(),
                'active' => $active,
                'inactive' => $inactive,
                'user' => $user
            ];

            return $this->sendResponse($response, 'Organization cards displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }


    public function switchOrganization()
    {
        $orgId = request('orgId');
        $authId = auth()->user()->id;
        $authName = auth()->user()->name;

        try {
            $adminOrg = [
                'id' => null,
                'name' => config('custom.admin_organization'),
                'short_name' => null,
                'role_name' => roleNameReadable(RoleEnum::SuperAdmin),
            ];

            $org = isSuperAdmin($authId)
                ? $this->getSuperAdminOrganizations($adminOrg, $orgId)
                : $this->getUserOrganizations($authId, $authName);

            $orgRecords = CommonResource::collection($org)->response()->getData(true);
            return $this->sendResponse($orgRecords, 'Organization listed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Fetch organizations for SuperAdmin.
     */
    private function getSuperAdminOrganizations($adminOrg, $orgId)
    {
        $org = OrganizationModel::select('id', 'name', 'short_name')
            ->with(['roles:roles.id,roles.name'])
            ->whereStatus('active')
            ->get();

        $org->makeHidden('email');
        $org->transform(function ($item) {
            $item->role_name = roleNameReadable($item->roles->name);
            unset($item->roles, $item->email);
            return $item;
        });
        if ($orgId) {
            $org->prepend($adminOrg);
        }
        return $org;
    }

    /**
     * Fetch organizations for regular users.
     */
    private function getUserOrganizations($authId, $authName)
    {
        $orgData = UserRoleOrgModel::with([
            'organization:id,name,short_name',
            'roles:id,name',
        ])
            ->where('user_id', $authId)
            ->get();

        return $orgData->map(function ($item) use ($authName) {
            return [
                'id' => $item->organization->id ?? null,
                'name' => $item->organization->name ?? $authName,
                'short_name' => $item->organization->short_name ?? null,
                'role_name' => roleNameReadable($item->roles->name),
            ];
        });
    }


    

    public function showAssign()
    {
        try {
            $org = OrganizationModel::select('id', 'name')
                ->with([
                    'roles' => function ($query) {
                        $query->select('roles.id', 'roles.name');
                    }
                ])
                ->where('status', 'active')
                ->get();
            $org->makeHidden('email');
            $org->map(function ($item) {
                unset($item->organization, $item->roles);
                return $item;
            });

            return $this->sendResponse($org, 'Organization listed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     *
     */
    public function edit($id)
    {
        try {
            $organization = OrganizationModel::select('id', 'name')->find($id);
            return $this->sendResponse($organization, 'Organization showed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     *
     */
    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:20|unique:organizations',
        ], [
            'name.required' => 'Organization name is required.',
            'name.string' => 'Organization name must be a valid string.',
            'name.max' => 'Organization name must not be greater than 20 characters',
            'name.unique' => 'Organization name has already been used. Please choose a different one.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        try {
            $organization = OrganizationModel::find($id);
            $organization->name = $request->name;
            $organization->save();
            LogHelper::logAction('Updated', 'Organization', "User updated '{$request->input('name')}' organization", getRoleId(), $organization->id);
            return $this->sendResponse(null, 'Updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function invite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', new ValidEmail(), 'max:255', Rule::unique('users')],
            'role_id' => 'required|integer|in:1,2,3,4,5,6',
            'route' => 'required|string',
            'orgId' => [
                Rule::requiredIf($request->role_id == RoleNameOrId(null, RoleEnum::OrgAdmin) || $request->role_id == RoleNameOrId(null, RoleEnum::OrgUser)),
                'nullable',
                'integer',
                'exists:organizations,id'
            ],
            'name' => [
                Rule::requiredIf($request->role_id == RoleNameOrId(null, RoleEnum::OrgSuperAdmin)),
                'string',
                'max:20',
                Rule::unique('organizations', 'name')
            ]
        ], [
            'email.required' => 'Email is required.',
            'email.max' => 'Email must not be greater than 255 characters',
            'email.unique' => 'Email has already been used. Please choose a different one.',
            'role_id.required' => 'Role is required.',
            'role_id.integer' => 'Role must be an integer.',
            'role_id.in' => 'Role is invalid.',
            'route.required' => 'Route is required.',
            'route.string' => 'Route must be a valid string.',
            'orgId.required' => 'Organization is required.',
            'orgId.integer' => 'Organization must be an integer.',
            'name.string' => 'Organization must be a valid string.',
            'name.max' => 'Organization name must not be greater than 255 characters',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if (!getRolePrevilage($request->role_id)) {
            return $this->sendError('Your role does not have permission to access this request', 403);
        }


     

        DB::beginTransaction();
        try {
            $parent_id = match ($request->role_id) {
                RoleNameOrId(null, RoleEnum::OrgAdmin), RoleNameOrId(null, RoleEnum::OrgUser) => $request->orgId,
                default => null
            };

            $organization = null;

            if ($request->role_id == RoleNameOrId(null, RoleEnum::OrgSuperAdmin)) {
                $organization = OrganizationModel::create([
                    'name' => $request->name,
                    'status' => 'inactive'
                ]);
                $parent_id = $organization->id;
            }

            $_token = Str::random(30);
            $route = "{$request->route}/{$_token}";

            $user = User::create([
                'email' => $request->email,
                'user_status' => 'invited',
                'status' => 1,
            ]);

            PasswordReset::updateOrInsert(['email' => $request->email], [
                'token' => $_token,
                'expires_at' => now()->addDay(),
            ]);

            if ($organization) {
                $organization->users()->attach($user->id, ['role_id' => $request->role_id]);
                $orgName = $organization->name;
            } else if($request->role_id == RoleNameOrId(null, RoleEnum::Admin) || $request->role_id == RoleNameOrId(null, RoleEnum::User) || request()->role_id == RoleNameOrId(null, RoleEnum::SuperAdmin)) {
                UserRoleOrgModel::create([
                        'user_id' => $user->id,
                        'organization_id' => NULL,
                        'role_id' => $request->role_id,
                    ]);
                    $orgName = config('custom.admin_organization');
            }else {
                $user->organizations()->attach($parent_id, ['role_id' => $request->role_id]);
                $orgName = $request->role_id ? getOrgName($request->orgId) : config('custom.admin_organization');
            }

            DB::commit();

            $mailData = [
                'subject' => 'Invitation to Join',
                'inviteFlow' => true,
                'route' => $route
            ];

            \Notification::route('mail', $request->email)->notify(new CustomNotification($mailData));

            $module = ($organization) ? 'organization' : 'user';

            LogHelper::logAction('Added', $module, "Added new '{$module}' '{$orgName}'", getRoleId(), $organization->id ?? NULL);

            return $this->sendResponse(['status' => 'Success'], 'User created successfully and invite link has been sent to E-mail.');
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->sendError('An error occurred: ' . $e->getMessage(), null, 500);
        }
    }

    public function reInvite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'route' => 'required|string',
            'orgId' => auth()->user()->hasRole(RoleEnum::SuperAdmin) ? 'nullable' : "required",
        ], [
            'email.required' => 'Email is required.',
            'email.string' => 'Email must be a valid string.',
            'email.max' => 'Organization name must not be greater than 255 characters',
            'route.required' => 'Route is required.',
            'route.string' => 'Route must be a valid string.',
            'orgId.required' => 'Organization is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->sendError('User not found.');
        }

        $roleId = UserRoleOrgModel::where('user_id', $user->id)
            ->when($request->orgId, function ($query) use ($request) {
                $query->where('organization_id', $request->orgId);
            })->value('role_id');

        if (!$roleId) {
            return $this->sendError('Role not found for the given user and organization.');
        }

        if (!postIsallowed($request->orgId) || !getRolePrevilage($roleId)) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        $user_check = User::where('email', $request->email)->first();
        $user_name = $user_check->name;

        if ($user_check->name) {
            return $this->sendError('The user has already signed in.');
        }

        DB::beginTransaction();

        try {
            User::where('email', $request->email)->update([
                'created_at' => now(),
            ]);
            $_token = Str::random(30);
            $route = $request->route . '/' . $_token;
            $check = PasswordReset::updateOrInsert(['email' => $request->input('email')], 
                [
                    'token' => $_token,
                    'expires_at' => now()->addDay()
                ]);

            DB::commit();
            $mailData = [
                'subject' => 'Invitation to Join',
                'inviteFlow' => true,
                'route' => $route
            ];

            // \Notification::route('mail', $request->input('email'))->notify(new ResendInvite($route));
            \Notification::route('mail', $request->input('email'))->notify(new CustomNotification($mailData));

            LogHelper::logAction('Resent', 'User', ($user_name) ? "Resend invite has sent to the user : {$user_name}" : 'Resend invite has sent to the user', getRoleId(), $request->orgId);

            return $this->sendResponse(null, 'Invitation has been resent successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function roles(Request $request)
    {
        
        $auth_id = Auth::user()->id;
        $orgId = $request->input('orgId') ?? null;

        if(!isSuperAdmin($auth_id)) {
            if (!$orgId) {
                return $this->sendError('Organization is mandatory');
            }
        }

        $type = $request->input('type');

        try {
            if ($type) {
                $roleNames = match ($type) {
                    'add' => [RoleEnum::OrgSuperAdmin],
                    'assign' => [RoleEnum::OrgAdmin, RoleEnum::OrgUser],
                    default => null
                };

                if (!$roleNames) {
                    return $this->sendError('Invalid type provided.');
                }

                $roles = RoleModel::select('id', 'name')->whereIn('name', $roleNames)->get();

                $roles->transform(fn ($role) => tap($role, fn ($r) => $r->name = roleNameReadable($r->name)));

                return $this->sendResponse($roles, 'Roles displayed successfully.');
            }

             $userRoleOrg = UserRoleOrgModel::when(!isSuperAdmin($auth_id), function ($query) use ($orgId) {    
                    $query->where('organization_id', $orgId);
                })->where('user_id', Auth::id())
                ->first();


            $roleNames = match ($userRoleOrg->role_id) {
                1 => [RoleEnum::SuperAdmin, RoleEnum::Admin, RoleEnum::User],
                2 => [RoleEnum::User],
                4 => [RoleEnum::OrgAdmin, RoleEnum::OrgUser],
                5 => [RoleEnum::OrgUser],
                default => []
            };

            $roles = RoleModel::select('id', 'name')->whereIn('name', $roleNames)->get()
                ->transform(fn ($role) => tap($role, fn ($r) => $r->name = roleNameReadable($r->name)));

            return $this->sendResponse($roles, 'Roles displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Filter roles
     */
    public function filterRoles(Request $request)
    {
        $type = $request->input('type');

        try {
            $roleNames = match ($type) {
                'admin' => [RoleEnum::Admin, RoleEnum::User],
                'org' => [RoleEnum::OrgAdmin, RoleEnum::OrgUser, RoleEnum::OrgSuperAdmin],
                default => null
            };

            if (!$roleNames) {
                return $this->sendError('Invalid type provided.');
            }

            $roles = RoleModel::select('name')->whereIn('name', $roleNames)->get();

            $roles->transform(fn ($role) => tap($role, function ($r) {
                $r->value = $r->name;
                $r->name = roleNameReadable($r->name);
            }));

            return $this->sendResponse($roles, 'Roles displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     *
     */
    public function enableDisableOrganization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id' => 'required'
        ], [
            'status.required' => 'Status is required.',
            'status.in' => 'Status is invalid.',
            'id.required' => 'Organization is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        try {
            $org = OrganizationModel::find($request->id);
            $org->status = $request->status;
            $action = $request->status == 'active' ? 'enable' : 'disable';
            if ($org->save()) {
                LogHelper::logAction(ucfirst($action . 'd'), 'Organization', "User {$action}d the {$org->name} organization", getRoleId(), $org->id);
                return $this->sendResponse(null, 'Status changed successfully.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * User Registration form submit API
     */
    public function register(Request $request)
    {
        $captchaOnOff = config('custom.captcha_on_off');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:20',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            ],
            'confirm_password' => 'required|same:password',
            'token' => 'required',
            'g-recaptcha-response' => [$captchaOnOff == 'ON' ? 'required' : '', new Recaptcha()],
            // 'status' => ['required', Rule::in(['active', 'inactive'])]
        ], [
            'name.required' => 'Organization name is required.',
            'name.string' => 'Organization name must be a valid string.',
            'name.max' => 'Organization name must not be greater than 255 characters',
            'password.required' => 'Password is required.',
            'confirm_password.required' => 'Confirm Password is required.',
            'confirm_password.same' => 'Password & Confirm Password do not match.',
            'token.required' => 'Token is required.',
            'g-recaptcha-response.required' => 'g-recaptcha-response is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $data = PasswordReset::where('token', $request->token)->first();

        $expiresAt = Carbon::parse($data->expires_at);

        // Parse the current time as a Carbon instance
        $now = Carbon::parse(now());

        if ($now->gte($expiresAt)) {
            return $this->sendError('The given link is expired');
        }
        DB::beginTransaction();

        try {
            $user = User::where('email', $data->email)->first();

            if ($user) {
                $user->name = $request->name;
                $user->password = Hash::make($request->password);
                $user->save();
            }

            $user_role_org = UserRoleOrgModel::where('user_id', $user->id)->where('role_id', RoleNameOrId(null, RoleEnum::OrgSuperAdmin))->first();

            if ($user_role_org) {
                $orgId = $user_role_org->organization_id;
                $org = OrganizationModel::where('id', $orgId)->update(['status' => 'active']);
            }

            $del = PasswordReset::where('token', $request->token)->delete();

            DB::commit();
            return $this->sendResponse(null, 'Created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Verify Link Expiration
     */
    public function verifyInvitationLink($token)
    {
        try {
            $data = PasswordReset::where('token', $token)->first();

            if (!$data) {
                return $this->sendError('Link is not valid. Please contact the administrator', null, 400);
            }

            $expiresAt = Carbon::parse($data->expires_at);

            $now = Carbon::parse(now());

            if ($now->gte($expiresAt)) {
                User::where('email', $data->email)->update([
                    'user_status' => 'Expired',
                ]);
                return $this->sendError('The link has expired.');
            }
            User::where('email', $data->email)->update([
                'email_verified_at' => now(),
            ]);

            return $this->sendResponse(['email' => $data->email], 'Success');
        } catch (\Exception $e) {
            $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * get org info
     */
    public function getInfo($orgId)
    {
        try {
            $org = OrganizationModel::select('id', 'name', 'dark_logo', 'short_name')->with('tickets', function ($q) {
                $q->select('organization_id', 'type', 'values');
            })->where('id', $orgId)->first();

            if (!$org) {
                return $this->sendError('Invalid Identifier');
            }

            $tickets = optional($org->tickets);
            $tickets->values = $tickets->values ? json_decode($tickets->values) : null;

            if ($tickets->values && isset($tickets->values->url)) {
                $domain = parse_url($tickets->values->url, PHP_URL_HOST);
                $tickets->values->url = explode('.', $domain)[0] ?? null;
            }

            return $this->sendResponse($org, 'Organization displayed successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function info(Request $request)
    {
        $rules = [
            'orgId' => 'required|integer|exists:organizations,id',
            'short_name' => 'required',
            'name' => [
                'required',
                'max:255',
                Rule::unique('organizations')->ignore($request->orgId, 'id')
            ],
            'type' => 'nullable|in:trello,jira,freshservice',
            'board_name' => 'required_if:type,trello,jira|nullable|string',
            'list_name' => 'required_if:type,trello|nullable|string',
            'key' => 'required_if:type,trello,jira,freshservice|nullable|string',
            'token' => 'required_if:type,trello|nullable|string',
            'domain' => 'required_if:type,jira,freshservice|nullable|string',
            'username' => 'required_if:type,jira|nullable|string',
        ];

        $messages = [
            'short_name.required' => 'Short name is required.',
            'name.required' => 'Organization name is required.',
            'name.max' => 'Organization name must not be greater than 255 characters',
            'name.unique' => 'This organization name is already taken. Please choose a different one.',
            'board_name.required_if' => 'Board name is required when type is Trello or Jira.',
            'board_name.string' => 'Board name must be a valid text.',
            'list_name.required_if' => 'List name is required when type is Trello.',
            'list_name.string' => 'List name must be a valid text.',
            'key.required_if' => 'API key is required when type is Trello, Jira, or Freshservice.',
            'key.string' => 'API key must be a valid text.',
            'token.required_if' => 'Token is required when type is Trello.',
            'token.string' => 'Token must be a valid text.',
            'domain.required_if' => 'Domain is required when type is Jira or Freshservice.',
            'domain.string' => 'Domain must be a valid text.',
            'username.required_if' => 'Username is required when type is Jira.',
            'username.string' => 'Username must be a valid text.',
            'orgId' => 'organization is required.',
        ];

        $orgId = $request->orgId;

        if (!postIsallowed($orgId)) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        // if (!$org->dark_logo) {
        //     $rules['dark_logo'] = 'required|mimes:jpg,png|max:5120';
        // }

        // Check if dark_logo exists in the request
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $path = folderFindOrCreate($orgId);

        DB::beginTransaction();

        try {
            // Find the organization
            $org = OrganizationModel::find($orgId);

            $updateData = [
                'name' => $request->name,
                'short_name' => $request->short_name,
            ];

            $org_name = $org->name;

            if ($request->hasFile('dark_logo')) {
                if ($org->dark_logo) {
                    $filePathD = $org->folder_path . '/' . $org->dark_logo;
                    if (Storage::disk('public')->exists($filePathD)) {
                        Storage::disk('public')->delete($filePathD);
                    }
                }
                $file_dark_logo = $request->file('dark_logo');
                $dark_extension = $file_dark_logo->getClientOriginalExtension();
                $darkLogo = 'darklogo.' . $dark_extension;
                $file_dark_logo->storeAs($path, $darkLogo, 'public');
                $updateData['dark_logo'] = $darkLogo;
            }

            $org->update($updateData);

            if ($request->type) {
                $data = []; // Will hold specific type data

                $user_org = OrganizationModel::with([
                    'users' => function ($query) use ($orgId) {
                        // $query->where('user_id', '!=', getSuperAdminId());
                        $query->where('role_id', RoleNameOrId(null, RoleEnum::OrgSuperAdmin));
                        $query->where('organization_id', $orgId);
                    }
                ])
                    ->whereId($orgId)
                    ->first();

                $org_email = $user_org->users[0]->email;

                switch ($request->type) {
                    case 'trello':
                        $result = $this->trelloService->getBoardIdAndListId($request->board_name, $request->list_name, $request->key, $request->token);

                        if (!$result || array_key_exists('error', $result)) {
                            return $this->sendError($result['error'] ?? 'Invalid Trello credentials.');
                        }

                        $data = [
                            'board_id' => $result['board_id'],
                            'list_id' => $result['list_id'],
                            'board_name' => $request->board_name,
                            'list_name' => $request->list_name,
                            'key' => $request->key,
                            'token' => $request->token,
                            'url' => 'https://api.trello.com',
                            'email' => $org_email
                        ];
                        break;

                    case 'freshservice':
                        $freshService = $this->freshService->validateCredentials($request->domain, $request->key);

                        if (array_key_exists('error', $freshService)) {
                            return $this->sendError($freshService['error']);
                        }

                        $urlc = "https://{$request->domain}.freshservice.com";

                        $data = [
                            'key' => $request->key,
                            'url' => $urlc,
                            'email' => $org_email
                        ];
                        break;

                    case 'jira':
                        $isValid = $this->jiraService->validateCredentials($request->username, $request->key, $request->domain);

                        if (!$isValid) {
                            return $this->sendError('Invalid Jira credentials.');
                        }

                        $urlc = "https://{$request->domain}.atlassian.net";

                        $data = [
                            'key' => $request->key,
                            'url' => $urlc,
                            'username' => $request->username,
                            'board_name' => $request->board_name,
                            'email' => $org_email
                        ];
                        break;
                }

                // Common updateOrInsert logic
                TicketingTool::updateOrInsert([
                    'organization_id' => $request->orgId,
                ], [
                    'values' => json_encode($data),
                    'type' => $request->type,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            } else {
                $ticket = TicketingTool::where('organization_id', $request->orgId)->get();
                if ($ticket) {
                    TicketingTool::where('organization_id', $request->orgId)->delete();
                }
            }

            DB::commit();

            LogHelper::logAction('Updated', 'Organization', "Organization '{$org_name}' updated ", getRoleId(),$org->id);

            return $this->sendResponse(null, 'Organization updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError($e->getMessage(), null, 500);
        }
    }

 

    public function editRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orgId' => 'required',
            'roleId' => 'required',
            'userId' => 'required'
        ], [
            'orgId.required' => 'Organization is required.',
            'roleId.required' => 'Role is required.',
            'userId.required' => 'User is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $authUserId = Auth::id();
        $targetUserId = $request->userId;

        if ($authUserId == $targetUserId) {
            return $this->sendError('You cannot change your Role');
        }

        try {
            // Update the role
            $updateStatus = UserRoleOrgModel::where('user_id', $targetUserId)
                ->where('organization_id', $request->orgId)
                ->update(['role_id' => $request->input('roleId')]);

            if ($updateStatus) {
                $logoutStatus = other_logout($targetUserId);

                if ($logoutStatus) {
                    $roleName = RoleModel::where('id', $request->roleId)->value('name');
                    $roleName = Str::title(str_replace('_', ' ', $roleName));

                    $userEmail = User::where('id', $targetUserId)->value('email');

                    if ($roleName && $userEmail) {
                        $mailData = [
                            'subject' => 'Role changed',
                            'bodyText' => "Your Role has been changed by Administrator<br>Your Current Role: <b>{$roleName}</b>"
                        ];

                        \Notification::route('mail', $userEmail)->notify(new CustomNotification($mailData));
                    }
                }

                // Get user details once
                $targetUser = User::find($targetUserId);

                if ($targetUser) {
                    $userName = $targetUser->name;
                    $userEmail = $targetUser->email;

                    $logMessage = $userName
                        ? "User updated the role for '{$userName}' organization"
                        : "'{$userEmail}' This user role has been updated";

                    // LogHelper::logAction('Updated', 'User', $logMessage, getRoleId(), getSuperAdminOrgId());
                    LogHelper::logAction('Updated', 'User', $logMessage, getRoleId(), $request->orgId);
                }

                return $this->sendResponse(null, 'User role changed successfully');
            } else {
                return $this->sendError('No changes were made. Please try again later');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $organization = OrganizationModel::find($id);
            $org_name = $organization->name;
            $users = $organization->users;

            foreach ($users as $key => $user) {
                if ($user->roles->count() == 1) {
                    $user->forceDelete();
                } elseif ($user->roles->count() > 1) {
                    $users_data = UserRoleOrgModel::where('organization_id', $organization->id)->delete();
                }
            }
            $organization->delete();

            DB::commit();

            LogHelper::logAction('Deleted', 'Organization', "User deleted '{$org_name}' organization", getRoleId(), $id);

            return $this->sendResponse(null, 'Organization deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), null, 404);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }
}
