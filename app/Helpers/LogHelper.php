<?php

namespace App\Helpers;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class LogHelper
{
    public static function logAction($action, $module, $details, $role_id, $orgId= null)
    {
        $log = new Log();

        $log->user_id = Auth::user()->id;
        $log->role_id = $role_id;
        $log->date = now();
        $log->action = $action;
        $log->module = $module;
        $log->details = $details; 
        $log->org_name = ($orgId) ? getOrgName($orgId) : null; 
        $log->user_name = Auth::user()->name ?? 'Guest';
        $log->user_email = Auth::user()->email ?? 'guest@example.com';
        $log->user_ip = request()->ip();
        $log->save();
    }
}