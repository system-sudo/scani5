<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Asset;
use App\Models\Exploits;
use App\Models\Patch;
use App\Models\Scopes\ActiveVulnerabilityScope;
use App\Models\Vulnerability;
use App\ResponseApi;
use App\Models\OrganizationModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// use PhpOffice\PhpSpreadsheet\Calculation\LookupRef\Unique;

class DashboardController extends Controller
{
    use ResponseApi;

    /**
     * Admin Dashboard
     */
    public function admin(Request $request)
    {
        $day_value = request('dicsover_date', 1);

        $orgs_count = OrganizationModel::count();
        $users_count = User::whereHas('roles', function ($user) {
            // $user->where('name', '!=', RoleEnum::OrgSuperAdmin);
            $user->whereIn('name', [RoleEnum::Admin, RoleEnum::User]);
        });
        $assets_count = Asset::count();
        $org_active_count = OrganizationModel::where('status', 'active')->count();
        $org_inactive_count = OrganizationModel::where('status', 'inactive')->count();

        $sq1_user_count = User::whereHas('roles', function ($q) {
            $q->whereIn('name', [RoleEnum::Admin, RoleEnum::User]);
        })->count();

        $org_user_count = User::whereHas('roles', function ($q) {
            $q->whereIn('name', [RoleEnum::OrgAdmin, RoleEnum::OrgUser]);
        })->count();

        $verified_users_count = (clone $users_count)->whereNotNull('mfa_token')->count();

        $invited_users_count = (clone $users_count)->whereNull('mfa_token')->where('created_at', '>', Carbon::now()->subDay())->count();

        $expired_users_count = (clone $users_count)->whereNull('mfa_token')->where('created_at', '<', Carbon::now()->subDay())->count();

        $org_chart = (object) [
            'value' => [$org_active_count, $org_inactive_count],
            'name' => ['Active', 'Inactive'],
            'fill' => ['#3bc1c3', '#ff8930']
        ];

        $users_chart = (object) [
            'name' => ['SQ1 user', 'Organization user'],
            'value' => [$sq1_user_count, $org_user_count],
            'fill' => ['#f34f7c', '#34b0df']
        ];

        $user_status_chart = (object) [
            'name' => ['verified', 'Invited', 'Expired'],
            'value' => [$verified_users_count, $invited_users_count, $expired_users_count],
            'fill' => ['rgb(1 139 58)', '#888888', 'rgb(221, 64, 16)']
        ];

        $discovered_vulnerabilities = Vulnerability::withoutGlobalScope(ActiveVulnerabilityScope::class)
            ->whereBetween('created_at', [
                Carbon::now()->subDays($day_value)->startOfDay(),
                Carbon::now()->endOfDay(),
            ])->count();

        $result = [
            'orgs_count' => $orgs_count,
            'discovered_vulnerabilities' => $discovered_vulnerabilities,
            'users_count' => (clone $users_count)->count(),
            'assets_count' => $assets_count,
            'org_chart' => $org_chart,
            'users_chart' => $users_chart,
            'user_status_chart' => $user_status_chart,
        ];

        return $this->sendResponse($result, 'Dashboard loaded successfully.');
    }

    /**
     * Organization dashboard
     */
    public function organization(Request $request)
    {
        $orgId = $request->get('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        $orgDetails = OrganizationModel::select('id', 'name', 'short_name')
            ->where('id', $orgId)
            ->first();

        $assets = Asset::where('organization_id', $orgId);
        $assetsCount = (clone $assets)->count();

        $criticalAssetCount = clone $assets;
        $criticalAssetCount = $criticalAssetCount->whereBetween('rti_score', [9, 10])->count();

        $active_assets = (clone $assets)->where('agent_status', 'connected')->count();
        $inactive_assets = (clone $assets)->where('agent_status', 'disconnected')->count();

        $vulnerabilitiesQuery = Vulnerability::select('id', 'name', 'risk', 'first_seen', 'last_identified_on', 'created_at', 'status', 'CVEs')
            ->whereHas('assetrelations', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            });

        $vulnerabilities = clone $vulnerabilitiesQuery->get();
        $vulRec = clone $vulnerabilitiesQuery;
        $vulIds = $vulRec->pluck('id');
        $exploitsCount = Exploits::whereIn('vul_id', $vulIds)->count();

        $result = [
            'org_details' => $orgDetails,
            //first level card
            'vulnerabilities_count' => $vulnerabilities->count(),
            'exploits_count' => $exploitsCount,
            'critical_assets_count' => $criticalAssetCount,
            'assets_count' => $assetsCount,
            'inactive_assets' => $inactive_assets,
            'active_assets' => $active_assets,
        ];

        return $this->sendResponse($result, 'Dashboard loaded successfully.');
    }

    public function riskDistribution(Request $request)
    {
        $orgId = $request->get('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        $vulnerabilities = Vulnerability::select('id', 'name', 'risk', 'first_seen', 'last_identified_on', 'created_at', 'status', 'CVEs')
            ->whereHas('assetrelations', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            })->get();

        $startDate = Carbon::today()->subYear()->addMonth(1)->startOfMonth();
        $endDate = Carbon::today()->endOfMonth();
        $diffInMonths = $startDate->diffInMonths($endDate);

        $riskLevels = getRiskStatus();

        $riskData = [];

        foreach ($riskLevels as $level => $range) {
            $riskData[$level] = $this->getCountsByMonth($vulnerabilities, $range, $diffInMonths);
        }

        $riskRecords = array_map(function ($label, $data) {
            return [
                'label' => $label,
                'data' => array_map(function ($month, $value) {
                    return ['month' => $month, 'value' => $value];
                }, array_keys($data), $data),
            ];
        }, array_keys($riskData), $riskData);

        $riskDistribution = [
            'current_month' => Carbon::now()->month,
            'dataset' => $riskRecords
        ];

        $result = [
            'risk_distribution' => $riskDistribution,
        ];

        return $this->sendResponse($result, 'Dashboard loaded successfully.');
    }

    public function ageMatrix(Request $request)
    {
        $orgId = $request->get('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        $today = Carbon::today();
        $thirtyDaysAgo = Carbon::today()->subDays(30);
        $thirtyOneDaysAgo = Carbon::today()->subDays(31);
        $sixtyDaysAgo = Carbon::today()->subDays(60);
        $sixtyOneDaysAgo = Carbon::today()->subDays(61);
        $ninetyDaysAgo = Carbon::today()->subDays(90);
        $ninetyOneDaysAgo = Carbon::today()->subDays(91);

        $timePeriods = [
            '>91 days' => [null, $ninetyOneDaysAgo],
            '61-90 days' => [$sixtyOneDaysAgo, $ninetyDaysAgo],
            '31-60 days' => [$thirtyOneDaysAgo, $sixtyDaysAgo],
            '<30 days' => [$thirtyDaysAgo, $today],
        ];
        $riskCategories = getRiskStatus();

        $publishDateAgeMatrix = [];
        foreach ($timePeriods as $timeLabel => [$start, $end]) {
            $counts = [];
            foreach ($riskCategories as $riskLabel => [$minRisk, $maxRisk]) {
                $query = Vulnerability::whereHas('assetrelations', function ($q) use ($orgId) {
                    $q->where('organization_id', $orgId);
                })
                    ->whereBetween('risk', [$minRisk, $maxRisk]);
                // Apply time period conditions
                if ($timeLabel === '>91 days') {
                    $query->where('created_at', '<', $ninetyOneDaysAgo);
                } elseif ($timeLabel === '<30 days') {
                    $query->where('created_at', '>', $thirtyDaysAgo);
                } elseif ($timeLabel === '61-90 days') {
                    $query->whereBetween('created_at', [$ninetyDaysAgo, $sixtyDaysAgo]);
                } elseif ($timeLabel === '31-60 days') {
                    $query->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo]);
                }
                $counts[$riskLabel] = $query->count();
            }
            $publishDateAgeMatrix[] = array_merge(['time' => $timeLabel], $counts);
        }

        // for discovered date age matrix chart start
        $discoveredDateAgeMatrix = [];
        foreach ($timePeriods as $timeLabel => [$start, $end]) {
            $counts = [];
            foreach ($riskCategories as $riskLabel => [$minRisk, $maxRisk]) {
                $query = Vulnerability::whereHas('assetrelations', function ($q) use ($orgId) {
                    $q->where('organization_id', $orgId);
                })
                    ->whereBetween('risk', [$minRisk, $maxRisk]);
                // Apply time period conditions
                if ($timeLabel === '>91 days') {
                    $query->where('first_seen', '<', $ninetyOneDaysAgo);
                } elseif ($timeLabel === '<30 days') {
                    $query->where('first_seen', '>', $thirtyDaysAgo);
                } elseif ($timeLabel === '61-90 days') {
                    $query->whereBetween('first_seen', [$ninetyDaysAgo, $sixtyDaysAgo]);
                } elseif ($timeLabel === '31-60 days') {
                    $query->whereBetween('first_seen', [$sixtyDaysAgo, $thirtyDaysAgo]);
                }
                $counts[$riskLabel] = $query->count();
            }
            $discoveredDateAgeMatrix[] = array_merge(['time' => $timeLabel], $counts);
        }

        $result = [
            'publish_date_age_matrix' => $publishDateAgeMatrix,
            'discovered_date_age_matrix' => $discoveredDateAgeMatrix,
        ];

        return $this->sendResponse($result, 'Dashboard loaded successfully.');
    }

    public function statusCharts(Request $request)
    {
        $orgId = $request->get('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        $vulnerabilitiesQuery = Vulnerability::select('id', 'name', 'risk', 'first_seen', 'last_identified_on', 'created_at', 'status', 'CVEs', 'severity')
            ->whereHas('assetrelations', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            })
            ->withCount(['assetrelations as asset_count' => function ($q_asset) use ($orgId) {
                $q_asset->whereHas('vulnerabilities', function ($query) use ($orgId) {
                    $query->where('organization_id', $orgId);
                });
            }]);

        $topVulnerabilities = (clone $vulnerabilitiesQuery)->orderByDesc('created_at')->limit(5)->get();

        $topVulnerabilities->map(function ($vul) {
            $vul->age = calculateAge($vul->first_seen);
            return $vul;
        });

        $avgRisk = round((clone $vulnerabilitiesQuery)->avg('risk'));

        $exposureScore = [
            'average_risk' => round(($avgRisk ?? 0) * 10),
        ];

        $exposureScore['risk_score'] = getRiskStatus($avgRisk);

        $workVulnerabilitiesCount = (clone $vulnerabilitiesQuery)->whereHas('assetrelations', function ($q) use ($orgId) {
            $q->where('type', 'workstation');
        })->count();
        $serverVulnerabilitiesCount = (clone $vulnerabilitiesQuery)->whereHas('assetrelations', function ($q) use ($orgId) {
            $q->where('type', 'server');
        })->count();

        $VulnerabilitiesByAssetCategory = [
            'Workstation' => $workVulnerabilitiesCount,
            'Server' => $serverVulnerabilitiesCount
        ];

        $VulnerabilitiesByAssetCategory = (object) [
            'value' => [$workVulnerabilitiesCount, $serverVulnerabilitiesCount],

            'name' => ['Workstation', 'Server'],

            'fill' => ['#7deb1d', '#f7a500']
        ];

        $vulWithoutScope = Vulnerability::withoutGlobalScope(ActiveVulnerabilityScope::class)
            ->select('id', 'name', 'risk', 'first_seen', 'last_identified_on', 'created_at', 'status', 'CVEs')
            ->whereHas('assetrelations', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            });

        // for Vulnerability overview
        $unpatched_vulnerability = (clone $vulWithoutScope)->where('status', 0)->count();
        $patched_vulnerability = (clone $vulWithoutScope)->where('status', 1)->count();

        $VulnerabilityStatus = [
            'Closed' => $patched_vulnerability,
            'Open' => $unpatched_vulnerability,
        ];

        $vulRec = clone $vulnerabilitiesQuery;
        $vulIds = $vulRec->pluck('id');
        $expRec = Exploits::whereIn('vul_id', $vulIds);
        $patchRec = Patch::whereIn('vul_id', $vulIds);

        $exploitableSummaryBySeverity = [
            'low' => (clone $expRec)->where('complexity', 'low')->count(),
            'medium' => (clone $expRec)->where('complexity', 'medium')->count(),
            'high' => (clone $expRec)->where('complexity', 'high')->count(),
            'critical' => (clone $expRec)->where('complexity', 'critical')->count(),
            'info' => (clone $expRec)->where('complexity', 'info')->count(),
        ];

        $patchByStatusSuccess = (clone $patchRec)->where('status', '1')->count();
        $patchByStatusFailed = (clone $patchRec)->where('status', '2')->count();

        $patchByStatus = (object) [
            'name' => ['Success', 'Failed'],
            'value' => [$patchByStatusSuccess, $patchByStatusFailed],
            'fill' => ['#01ac94', '#f8ec08']
        ];

        $result = [
            'vulnerabilities_by_asset_category' => $VulnerabilitiesByAssetCategory,
            'vulnerability_status' => $VulnerabilityStatus,
            'exploitable_summary_by_severity' => $exploitableSummaryBySeverity,
            'patch_by_status' => $patchByStatus,
            'exposure_score' => $exposureScore,
            'topVulnerabilities' => $topVulnerabilities,
        ];
        return $this->sendResponse($result, 'Dashboard loaded successfully.');
    }

    public function scanifyScore(Request $request)
    {
        $orgId = $request->get('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        $scani5ScoreVuls = Vulnerability::select('vulnerabilities.id', 'vulnerabilities.name', 'vulnerabilities.risk', 'vulnerabilities.first_seen', 'vulnerabilities.last_identified_on', 'vulnerabilities.created_at', DB::raw('COUNT(exploits.id) as exploit_count'), DB::raw('CASE
                    WHEN COUNT(exploits.id) >= 5 THEN "Critical"
                    WHEN COUNT(exploits.id) >= 3 THEN "High"
                    ELSE "none"
                    END as scanify_score'))
            ->join('exploits', function ($join) {
                $join->on('vulnerabilities.id', '=', 'exploits.vul_id');
            })
            ->whereHas('assetrelations', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            })
            ->where(function ($query) {
                $query->whereBetween('vulnerabilities.risk', [0, 3])
                    ->orWhereBetween('vulnerabilities.risk', [4, 6]);
            })
            ->groupBy('vulnerabilities.id')
            ->havingRaw('COUNT(exploits.id) >= 3')
            ->take(5)
            ->get();

        return $this->sendResponse($scani5ScoreVuls, 'Dashboard loaded successfully.');
    }

    /**
     *
     */
    private function getCountsByMonth($vul, $riskRange, $diffInMonths)
    {
        $counts = [];
        for ($i = 0; $i <= $diffInMonths; $i++) {
            $startDate = Carbon::today()->subYear()->addMonth(1)->startOfMonth()->addMonth($i);
            $endDate = $startDate->copy()->endOfMonth();
            $count = $vul->whereBetween('risk', $riskRange)
                ->whereBetween('first_seen', [$startDate, $endDate])
                ->count();
            $counts[$startDate->format('M')] = $count;
        }

        return $counts;
    }
}
