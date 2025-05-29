<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommonResource;
use App\ResponseApi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Log;
use Illuminate\Support\Facades\Cache;

class LogsController extends Controller
{
    use ResponseApi;

    /**
     * Logs
     */
    public function index()
    {
        $search = request('search');
        $sort_column = request('sort_column');
        $sort_direction = request('sort_direction');
        $accepted_sort_columns = ['action', 'module', 'details', 'date', 'user_ip', 'org_name'];
        $filter = json_decode(request('filter')) ?? null;

        $start = request('start', null) ? Carbon::parse(request('start'))->startOfDay() : null;
        $end = request('end', null) ? Carbon::parse(request('end'))->endOfDay() : null;

        try {
            $logs = Log::select('org_name', 'date', 'action', 'module', 'details', 'user_ip')
                ->search($search, ['action', 'module', 'details', 'user_email', 'user_name', 'user_ip'])
                ->where('user_id', auth()->user()->id)

                ->dateRangeFilter('date', $start, $end)
                ->when($filter, fn ($query) => $query->filter($filter))
                ->sort($sort_column, $sort_direction, $accepted_sort_columns)
                ->orderByDesc('date')
                ->paginateresults();

            $logRecords = CommonResource::collection($logs)->response()->getData(true);
            return $this->sendResponse($logRecords, 'Logs displayed successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function count()
    {
        try {
            $total = $log_actions = Log::where('user_id', auth()->user()->id)->count();

            $log_actions = Log::where('user_id', auth()->user()->id)
            ->selectRaw('action as label, COUNT(1) as count')
            ->groupBy('action')
            ->get();

            $log_actions->prepend([
                'label' => 'total',
                'count' => $total
            ]);
            return $this->sendResponse($log_actions, 'Logs cards displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function generateAuthString()
    {
        $randomString = bin2hex(random_bytes(16));

        Cache::put('log_deletion_auth_string', $randomString, now()->addMinutes(10)); // Valid for 10 minutes

        return $this->sendResponse($randomString, 'Auth string generated successfully');
    }

    // Delete logs older than 3 months
    public function deleteOldLogs(Request $request)
    {
        $threeMonthsAgo = Carbon::now()->subMonths(3);

        Log::where('created_at', '<', $threeMonthsAgo)->delete();

        return $this->sendResponse(null, 'Old logs deleted successfully');
    }
}
