<?php

namespace App\Http\Controllers;

use App\Events\NotificationCreated;
use App\Events\PostCreated;
use App\Http\Resources\CommonResource;
use App\Models\NotifyModel;
use App\Models\UserRoleOrgModel;
use App\ResponseApi;
use Auth;
use Illuminate\Http\Request;
use Validator;
use Pusher\Pusher;

class NotificationController extends Controller
{
    use ResponseApi;

    public function store(Request $request)
    {
        // Step 1: Validation
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:asset,vulnerability,other',
            'severity' => 'required|in:low,medium,high,critical',
            'message' => 'required|string|max:255',
            'orgId' => 'required|integer|exists:organizations,id',
        ], [
            'type.required' => 'Type is required.',
            'type.in' => 'Type is invalid.',
            'type.string' => 'Type must be a valid string.',
            'severity.required' => 'Severity is required.',
            'severity.in' => 'Severity is invalid.',
            'message.required' => 'Message is required.',
            'message.string' => 'Message must be a valid string.',
            'message.max' => 'Message must not be greater than 255 characters.',
            'orgId.required' => 'Organization is required.',
            'orgId.integer' => 'Organization must be an integer.',
            'orgId.exists' => 'Organization does not exist.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $orgId = $request->input('orgId');

        try {
            $userIds = UserRoleOrgModel::where('organization_id', $orgId)->pluck('user_id');

            $notifications = [];
            foreach ($userIds as $userId) {
                $notifications[] = [
                    'type' => $request->input('type'),
                    'severity' => $request->input('severity'),
                    'message' => $request->input('message'),
                    'organization_id' => $orgId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            NotifyModel::insert($notifications);

            $sampleNotification = $notifications[0] ?? null;

            // dd($sampleNotification);
            foreach ($userIds as $userId) {
                $sampleNotification['user_id'] = $userId;
                event(new NotificationCreated($sampleNotification));
            }


            // event(new NotificationCreated($sampleNotification));

            // if ($sampleNotification) {
            //     $pusher = new Pusher(config('broadcasting.connections.pusher.key'), config('broadcasting.connections.pusher.secret'), config('broadcasting.connections.pusher.app_id'), ['cluster' => config('broadcasting.connections.pusher.options.cluster')]);

            //     $pusher->trigger("app-notification_{$orgId}", 'notification', $sampleNotification);
            // }

            return $this->sendResponse(null, 'Notifications sent successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function index(Request $request)
    {
        $orgId = request('orgId');
        if (!allAdminRoles(auth()->user()->id)) {
            if (!$orgId) {
                return $this->sendError('Organization is mandatory');
            }
        }

        try {
            $type = strtolower(request('type', ''));
            $notifications = NotifyModel::select(['id',  'type', 'severity', 'message', 'created_at', 'read_at'])
                ->when($orgId, function ($query, $orgId) {
                    return $query->where('organization_id', $orgId);
                })
                ->where('user_id', Auth::user()->id)
                ->when($type, function ($query, $type) {
                    return $type === 'unread' ? $query->whereNull('read_at') : $query;
                })
                ->orderByDesc('created_at');

            $records = (clone $notifications)
                ->take(10)
                ->get();

            return $this->sendResponse($records, 'Notifications fetched successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function read($id, Request $request)
    {
        $notification = NotifyModel::whereId($id)->where('user_id', Auth::user()->id)->first();
        if (!$notification) {
            return $this->sendError('Notification not found', null, 404);
        }
        try {
            if (is_null($notification->read_at)) {
                $notification->read_at = now();
                $notification->save();
            }
            return $this->sendResponse(null, 'Notification marked as read successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function count(Request $request)
    {
        $orgId = request('orgId');
        $module = request('module') ? strtolower(request('module')) : null;

        $orgId = request('orgId');
        if (!allAdminRoles(auth()->user()->id)) {
            if (!$orgId) {
                return $this->sendError('Organization is mandatory');
            }
        }

        try {
            $notifyCount = NotifyModel::where('user_id', Auth::user()->id)
            ->when($orgId, function ($qu, $orgId) {
                return $qu->where('organization_id', $orgId);
            })
            ->when($module, function ($query, $module) {
                return $query->where('type', $module);
            });

            $totalCount = (clone $notifyCount)->count();
            $readCount = (clone $notifyCount)->whereNotNull('read_at')->count();
            $unreadCount = (clone $notifyCount)->whereNull('read_at')->count();

            $notifications = [];
            if ($totalCount) {
                $notifications = [
                    'all' => $totalCount,
                    'read' => $readCount,
                    'unread' => $unreadCount,
                ];
            }

            return $this->sendResponse($notifications, 'Notifications count displayed successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function indexAll(Request $request)
    {
        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        try {
            $type = request('type') ? strtolower(request('type')) : null;
            $module = request('module') ? strtolower(request('module')) : null;

            $notifications = NotifyModel::select('id', 'message', 'severity', 'type', 'created_at', 'read_at')
                ->when($orgId, fn ($org, $orgId) => $org->where('organization_id', $orgId))
                ->where('organization_id', $orgId)
                ->where('user_id', Auth::user()->id)
                ->when($type, function ($typ, $type) {
                    return $type === 'read' ? $typ->whereNotNull('read_at') :
                        ($type === 'unread' ? $typ->whereNull('read_at') : $typ);
                })
                ->when($module, function ($mod, $module) {
                    return $mod->where('type', $module);
                })
                ->orderByDesc('created_at')
                ->paginateresults();

            $notifications->makeHidden('read_at');

            $notifyRecords = CommonResource::collection($notifications)->response()->getData(true);

            return $this->sendResponse($notifyRecords, 'Notifications fetched successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function destroy($id, Request $request)
    {
        try {
            $notification = NotifyModel::find($id);
            if (!$notification) {
                return $this->sendError('Notification not found', null, 404);
            }
            $notification->delete();
            return $this->sendResponse(null, 'Notification deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }
}
