<?php

namespace App\Http\Controllers;

use App\Exports\AssetExport;
use App\Helpers\LogHelper;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\Scopes\ActiveVulnerabilityScope;
use App\Models\Tag;
use App\Models\Vulnerability;
use App\Notifications\CustomNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\ResponseApi;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use ZipArchive;

class AssetController extends Controller
{
    use ResponseApi;
    protected array $accepted_sort_columns = ['host_name', 'ip_address_v4', 'rti_score', 'severity', 'last_scanned'];

    public function index(Request $request)
    {
        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        $search = request('search') ? trim(request('search')) : null;
        $sort_column = request('sort_column');
        $sort_direction = request('sort_direction');
        $is_critical = request('critical_assets');
        $filter = json_decode(request('filter')) ?? null;
        $is_retired = request('is_retired');
        $is_reported = request('is_reported');
        // $is_vulnerability_asset = request('vulnerability_assets');
        $status = request('status');

        try {
            $assetsQuery = Asset::withCount('vulnerabilities')
                ->search($search, ['host_name', 'ip_address_v4', 'rti_score', 'severity', 'type', 'os'])
                ->where('organization_id', $orgId)
                ->when($filter, fn($query) => $query->filter($filter))
                ->when($is_critical, function ($cra) {
                    $cra->whereBetween('rti_score', [9, 10]);
                })
                ->when($status, fn($st) => $st->where('agent_status', $status))
                //    ->when($is_vulnerability_asset, function ($va) {
                //        $va->whereHas('vulnerabilities');
                //    })
                ->when($is_retired, function ($retired) {
                    $retired->onlyTrashed();
                })
                ->when($is_reported, function ($reported) {
                    $reported->whereNotNull('comment');
                })
                ->sort($sort_column, $sort_direction, $this->accepted_sort_columns)
                ->orderBy('rti_score', 'desc')
                ->paginateresults();

            $assets = AssetResource::collection($assetsQuery)->response()->getData(true);

            return $this->sendResponse($assets, 'Assets displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function count(Request $request)
    {
        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }
        $vul_id = request('vulnerabilityId');
        $is_retired = request('is_retired');
        $is_critical = request('critical_assets');
        $is_reported = request('is_reported');
        // $is_vulnerability_asset = request('vulnerability_assets');
        $status = request('status');
        try {
            $assets = Asset::where('organization_id', $orgId)
                ->when($vul_id, function ($vul) use ($vul_id) {
                    $vul->whereHas('vulnerabilities', function ($query) use ($vul_id) {
                        $query->where('vulnerability_id', $vul_id);
                    });
                })->when($is_retired, function ($retired) {
                    $retired->onlyTrashed();
                })->when($status, fn($st) => $st->where('agent_status', $status))
                ->when($is_reported, function ($reported) {
                    $reported->whereNotNull('comment');
                })->when($is_critical, function ($cra) {
                    $cra->whereBetween('rti_score', [9, 10]);
                })
                // ->when($is_vulnerability_asset, function ($va) {
                //     $va->whereHas('vulnerabilities');
                // })
            ;
            $lowCounts = (clone $assets)->where('severity', 'low')->count();
            $mediumCounts = (clone $assets)->where('severity', 'medium')->count();
            $highCounts = (clone $assets)->where('severity', 'high')->count();
            $criticalCounts = (clone $assets)->where('severity', 'critical')->count();
            $response = [
                'total' => $assets->count(),
                'critical' => $criticalCounts,
                'high' => $highCounts,
                'medium' => $mediumCounts,
                'low' => $lowCounts
            ];

            return $this->sendResponse($response, 'Assets count displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function details($id)
    {
        $type = request('type') == 'retired' ? 'retired' : 'active';
        try {
            $asset = Asset::select('id', 'organization_id', 'severity', 'agent_status', 'host_id', 'host_name', 'ip_address_v4', 'ip_address_v6', 'last_scanned', 'created_at as created_on', 'address as location', 'last_system_boot', 'last_user_login as last_activity', 'os')
                ->when($type == 'retired', function ($qu) {
                    $qu->onlyTrashed();
                })
                ->find($id);

            if (!$asset) {
                return $this->sendError('Asset not found.', null, 404);
            }

            $org_name = getOrgName($asset->organization_id);
            $asset->organization_name = $org_name;
            if (!$asset) {
                return $this->sendError('Asset not found.', null, 404);
            }

            $vulnerabilitiesQuery = Vulnerability::withoutGlobalScope(ActiveVulnerabilityScope::class)
                ->when(in_array($type, ['retired', 'active']), function ($query) use ($type, $id) {
                    $relation = $type == 'retired' ? 'assetrelationsTrashed' : 'assetrelations';
                    $query->whereHas($relation, function ($q) use ($id) {
                        $q->where('asset_id', $id);
                    });
                });

            $total_vul = (clone $vulnerabilitiesQuery)->count();
            $critical = (clone $vulnerabilitiesQuery)->where('severity', 'critical')->count();
            $high = (clone $vulnerabilitiesQuery)->where('severity', 'high')->count();
            $medium = (clone $vulnerabilitiesQuery)->where('severity', 'medium')->count();
            $low = (clone $vulnerabilitiesQuery)->where('severity', 'low')->count();

            $patched = (clone $vulnerabilitiesQuery)->where('status', 1)->count();
            $unPatched = (clone $vulnerabilitiesQuery)->where('status', 0)->count();

            $patchNotAvailable = Vulnerability::withoutGlobalScope(ActiveVulnerabilityScope::class)->whereDoesntHave('patches', function ($q) {
            })->count();

            $vulnerabilities = [
                'critical' => $critical,
                'high' => $high,
                'medium' => $medium,
                'low' => $low,
                'total' => $total_vul,
                'patched' => $patched,
                'not_patched' => $unPatched,
                'patch_not_available' => $patchNotAvailable,
            ];
            $asset->vulnerabilities = $vulnerabilities;
            unset($asset->organization_id);

            return $this->sendResponse($asset, 'Asset details displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Export
     */
    public function export()
    {
        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        $search = request('search') ? trim(request('search')) : null;
        $sort_column = request('sort_column');
        $sort_direction = request('sort_direction');
        $is_password = request()->boolean('is_password');
        $password = request('password');
        $selectedId = request('selectedId') ? explode(',', request('selectedId')) : null;
        $filter = json_decode(request('filter')) ?? null;
        $is_retired = request('is_retired');
        $is_reported = request('is_reported');

        $is_critical = request('critical_assets');

        try {
            deleteExportFolder();
            createExportFolder();

            $assets = Asset::search($search, ['host_name', 'ip_address_v4', 'rti_score', 'severity', 'type', 'os'])
                ->withCount(['vulnerabilities'])
                ->where('organization_id', $orgId)
                ->when($selectedId, fn($query) => $query->whereIn('id', $selectedId))
                ->when($is_critical, fn($query) => $query->whereBetween('rti_score', [9, 10]))
                ->when($is_retired, fn($query) => $query->onlyTrashed())
                ->when($is_reported, fn($query) => $query->whereNotNull('comment'))
                ->when($filter, fn($query) => $query->filter($filter))
                ->sort($sort_column, $sort_direction, $this->accepted_sort_columns)
                ->orderBy('rti_score', 'desc')
                ->get();

            $data = $assets->map(function ($asset) {
                return $asset->only([
                    'host_id',
                    'host_name',
                    'resource_id',
                    'ip_address_v4',
                    'ip_address_v6',
                    'os',
                    'rti_score',
                    'severity',
                    'type',
                    'agent_status',
                    'last_user_login',
                    'last_scanned',
                    'last_system_boot',
                    'last_checked_in',
                    'vulnerabilities_count'
                ]);
            });

            $now = Carbon::now()->format('Y_m_d_H_i_s_v');
            $filename = "scani5_asset_export_{$now}.xlsx";

            $export = new AssetExport($data);
            Excel::store($export, "exports/{$filename}", 'public');
            if ($is_password) {
                if (!$password) {
                    return $this->sendError('Password is empty');
                }

                $zipFilename = "scani5_asset_export_{$now}.zip";
                $filePath = storage_path("app/public/exports/{$filename}");
                $zipFilePath = public_path("exports/{$zipFilename}");
                resetExportFolder();

                $zip = new ZipArchive();
                if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                    $zip->addFile($filePath, basename($filePath));
                    $zip->setPassword($password);
                    $zip->setEncryptionName(basename($filePath), ZipArchive::EM_TRAD_PKWARE);
                    $zip->close();
                    $url = [
                        'file' => $zipFilename,
                        'fileName' => exportFileName($orgId, 'asset', 'zip'),
                    ];
                    LogHelper::logAction('Exported', 'Asset', 'User exported the assets', getRoleId(), $orgId);
                    return $this->sendResponse($url, 'Assets exported successfully.');

                } else {
                    return $this->sendError('Failed to create the zip file.');
                }
            } else {
                $url = [
                    'file' => $filename,
                    'fileName' => exportFileName($orgId, 'asset', 'xlsx'),
                ];
                LogHelper::logAction('Exported', 'Asset', 'User exported the assets', getRoleId(), $orgId);
                return $this->sendResponse($url, 'Assets exported successfully.');
            }


        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     *  Retire asset
     */
    public function retireAsset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'orgId' => 'required',
        ], [
            'id.required' => 'Asset is required.',
            'orgId.required' => 'Organization is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if (!postIsallowed($request->orgId)) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        $id = $request->id;

        try {
            $asset = Asset::find($id);

            if (!$asset) {
                return $this->sendError('Asset Not Found', null, 404);
            }

            $asset_ip = $asset->ip_address_v4;
            $asset->delete();

            LogHelper::logAction('Retired', 'Asset', "User deleted this IP : {$asset_ip}", getRoleId(), $request->orgId);
            return $this->sendResponse(null, 'Asset retired successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError($e->getMessage(), null, 404);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Report Asset with comments
     */
    public function revokeAsset($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orgId' => 'required',
        ], [
            'orgId.required' => 'Organization is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if (!postIsallowed($request->orgId)) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        try {
            $asset = Asset::whereId($id)->onlyTrashed()->first();

            if (!$asset) {
                return $this->sendError('Asset Not Found', null, 404);
            }

            $asset_ip = $asset->ip_address_v4;
            $asset->deleted_at = null;
            $asset->save();

            LogHelper::logAction('Revoked', 'Asset', "User Revoked this IP : {$asset_ip}", getRoleId(), $request->orgId);
            return $this->sendResponse(null, 'Asset revoked successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError($e->getMessage(), null, 404);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Report Asset with comments
     */
    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:255',
            'id' => 'required|exists:assets,id',
            'orgId' => 'required'
        ], [
            'id.required' => 'Asset is required.',
            'id.exists' => 'Asset does not exist.',
            'orgId.required' => 'Organization is required.',
            'comment.required' => 'Comments is required.',
            'comment.max' => 'Comments must not be greater than 255 characters',
            'comment.string' => 'Comments must be a valid string.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if (!postIsallowed($request->orgId)) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        $orgEmail = getOrgEmail($request->input('orgId'));
        $orgName = getOrgName($request->input('orgId'));

        $reported_date = Carbon::now()->format('d-M-Y H:i');
        $created_by = Auth::user()->name;

        try {
            $asset = Asset::find($request->input('id'));

            $asset->comment = $request->comment;
            $asset->save();
            $mailData = [
                'subject' => 'Report asset notification',
                'senderName' => $created_by,
                'recipientName' => 'Admin',
                'org_name' => $orgName,
                'bodyText' => 'This is to inform you that the following asset has been reported:<br>' .
                    "<b>Asset IP:</b>  {$asset->ip_address_v4}<br>" .
                    "<b>Host name:</b> {$asset->host_name}<br>" .
                    "<b>Date of report:</b> {$reported_date}<br>" .
                    "<b>Reported by:</b> {$created_by}<br>" .
                    "<b>Details of the reports:</b> {$asset->comment}"
            ];

            $asset_ip = $asset->ip_address_v4;

            LogHelper::logAction('Reported', 'Asset', "User reported this IP : {$asset_ip}", getRoleId(), $request->orgId);
            \Notification::route('mail', $orgEmail)->notify(new CustomNotification($mailData));
            return $this->sendResponse(null, 'Asset reported successfully.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while Reporting Assets.', null, 500);
        }
    }
}
