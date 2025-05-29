<?php

namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use App\Http\Resources\CommonResource;
use App\Models\Asset;
use App\Models\ReportModel;
use App\Models\Vulnerability;
use Carbon\Carbon;
use App\TCPDF\PDF;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\ResponseApi;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ResponseApi;

    public function __construct()
    {
        $this->client_name = 'Scani5-Assessment';
    }

    /**
     * Reports -index
     */
    public function index(Request $request)
    {
        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is required.');
        }

        $search = request('search');
        $sort_column = request('sort_column');
        $sort_direction = request('sort_direction');
        $accepted_sort_columns = ['name', 'created_at'];

        try {
            $reports = ReportModel::select('id', 'organization_id', 'name', 'created_at', 'created_by')
            ->search($search, ['name'])
            ->where('organization_id', $orgId)
            ->sort($sort_column, $sort_direction, $accepted_sort_columns)
            ->orderByDesc('created_at')
            ->paginateresults();

            $reportRecords = CommonResource::collection($reports)->response()->getData(true);

            return $this->sendResponse($reportRecords, 'Reports displayed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function count(Request $request)
    {
        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is required.');
        }

        $reports = ReportModel::where('organization_id', $orgId)->count();

        $response = [
            'total' => $reports,
        ];

        return $this->sendResponse($response, 'Report cards displayed successfully');
    }

    /**
     * Download report
     */
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'orgId' => 'required',
        ], [
            'id.required' => 'Report is required.',
            'orgId.required' => 'Organization is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if (!postIsallowed($request->orgId)) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        $report = ReportModel::find($request->id);

        $filePath = storage_path('app/public/' . $report->path . '/' . $report->name);

        $filename = $report->name;

        $fileContents = file_get_contents($filePath);
        if ($fileContents === false) {
            return $this->sendError('Failed to read the file');
        }
        $base64EncodedFile = base64_encode($fileContents);
        $response = [
            'pdf_base64' => $base64EncodedFile,
            'filename' => $filename,
        ];

        return $this->sendResponse($response, 'Downloaded successfully');
    }

    /**
     * Fetch ip in reports
     */
    public function showIp(Request $request)
    {
        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is required.');
        }

        $assets = Asset::select('id', 'ip_address_v4 as name')->where('organization_id', $orgId)->whereHas('vulnerabilities', function ($vulnerabilities) {
        })->get()->makeHidden('tag_value');

        return $this->sendResponse($assets, 'Ip address shown successfully');
    }

    /**
     * Vul report
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'orgId' => 'required',
        ], [
            'filename.required' => 'File is required.',
            'filename.string' => 'File must be a valid string.',
            'filename.max' => 'Filename must not be greater than 255 characters',
            'orgId.required' => 'Organization is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $orgId = $request->orgId;

        $reqname = app('request')->filename;

        $fromDate = (app('request')->fromDate) ? Carbon::createFromFormat('d/m/Y', app('request')->fromDate)->format('Y-m-d') : null;
        $toDate = (app('request')->toDate) ? Carbon::createFromFormat('d/m/Y', app('request')->toDate)->format('Y-m-d') : null;
        $type = (app('request')->type) ? app('request')->type : null;

        $ip_value = (app('request')->ipValue) ? app('request')->ipValue : null;
        $tag_value = (app('request')->tagValue) ? app('request')->tagValue : null;

        if (strtolower($type) == 'ips') {
            if (!$ip_value) {
                return $this->sendError('Ip address required');
            }
            $tag_value = null;
        }
        if (strtolower($type) == 'tags') {
            if (!$tag_value) {
                return $this->sendError('Tags required');
            }
            $ip_value = null;
        }

        if ($fromDate && !$toDate) {
            return $this->sendError('To date is required');
        }

        if (!postIsallowed($orgId)) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        $client_logo = getLogo($orgId)->dark_logo;

        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', '2048M');

        $style = $this->getStyle();

        $now = Carbon::now();
        $rep_date = $now->format('d/m/Y \a\t h:i A');
        $gmtOffset = '(GMT +05:30)';

        $report_date = $rep_date . ' ' . $gmtOffset;
        $scan_date = $now->format('Y_m_d_H_i_s_v');

        $vulnerabilityQuery = Vulnerability::whereHas('assetrelations', function ($asset) use ($orgId) {
            $asset->where('organization_id', $orgId);
        })
          ->when($fromDate && $toDate, function ($quo) use ($fromDate, $toDate) {
              $quo->whereBetween('created_at', [$fromDate, $toDate]);
          })
          ->when($type, function ($quor) use ($type, $ip_value, $tag_value) {
              switch (strtolower($type)) {
                  case 'ips':
                      $quor->whereHas('assetrelations', function ($asset) use ($ip_value) {
                          $asset->whereIn('ip_address_v4', $ip_value);
                      });
                      break;
                  case 'vulnerability_tags':
                      $quor->whereHas('tags', function ($tagQuery) use ($tag_value) {
                          $tagQuery->whereIn('tag_id', $tag_value);
                          $tagQuery->where('taggable_type', Vulnerability::class);
                      });
                      break;
                  case 'asset_tags':
                      $quor->whereHas('assetrelations', function ($asset) use ($tag_value) {
                          $asset->whereHas('tags', function ($tagQuery) use ($tag_value) {
                              $tagQuery->whereIn('tag_id', $tag_value);
                              $tagQuery->where('taggable_type', Asset::class);
                          });
                      });
                      break;
              }
          })
          ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')");

        $vulnerability = clone $vulnerabilityQuery->get();

        $critical = $vulnerability->where('severity', 'critical')->count();
        $high = $vulnerability->where('severity', 'high')->count();
        $medium = $vulnerability->where('severity', 'medium')->count();
        $low = $vulnerability->where('severity', 'low')->count();

        if (!count($vulnerability)) {
            return $this->sendError('No vulnerabilites. Failed to generate the report');
        }

        $vulnerable_chart = $this->getSeverity($vulnerability);

        PDF::Setup($orgId);
        PDF::setFontSpacing(0);
        PDF::setFontStretching(100);

        /******************* Main Page ************************/
        PDF::setPrintFooter(true);
        PDF::AddPage();
        $bMargin = PDF::getBreakMargin();
        $auto_page_break = PDF::getAutoPageBreak();
        PDF::SetAutoPageBreak(false, 0);
        $img_file = url('/images/reportFrontPage.png');
        PDF::Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        PDF::SetAutoPageBreak($auto_page_break, $bMargin);
        PDF::setPageMark();

        $main = <<<EOD
            $style

            <br/><br/><br/><br/>

            <h2 style="color: #fff; font-weight: normal;">Scani5</h2>

            <h2 style="color: #fff; font-weight: normal;">Assessment</h2><br/>

            <p style="font-size:18px;color: #fff;">
            Security Assessment Technical Report<br>
             <small style="font-size:13px;color: #fff;">$report_date</small>

             <small style="font-size:13px;color: #fff;">Version 1.0</small>
            </p>
EOD;
        if ($client_logo) {
            PDF::Image($client_logo, 15, 10, 35, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        // $SQ1Security = url('/images/sq1shield.png');

        // PDF::Image($SQ1Security, 160, 10, 35, '', 'PNG', '', 'L', false, 300, '', false, false, 0, false, false, false);
        // PDF::Image($SQ1Security, 160, 16, 35, '', 'PNG', '', 'L', false, 300, '',
        // false, false, 0, false, false, false);

        PDF::SetY(50);
        PDF::SetX(15);
        PDF::writeHTMLCell(0, 0, '', '', $main, 0, 1, 0, true, '', true);

        /******************* Confidentiality & Proprietary ************************/
        PDF::AddPage();
        PDF::setPrintFooter(true);

        PDF::Bookmark('Confidentiality Statements', 1, 0, '', 'C', [27, 117, 188]);
        PDF::Bookmark('Disclaimer', 1, 0, '', 'C', [27, 117, 188]);

        $page2 = <<<EOD
         $style
        <h4 style="color: #0070C0;">Confidentiality Statement</h4>
        <p>This document is the exclusive property of $this->client_name and SQ1Security. This document
        contains proprietary and confidential information. Duplication, redistribution, or use,
        in whole or in part, in any form, requires consent of both $this->client_name and SQ1Security.
        $this->client_name may share this document with auditors under non-disclosure agreements to
        demonstrate Scani5 Assessment requirement compliance.
        </p>

        <h4 style="color: #0070C0;">Disclaimer</h4>
        <p>A Scani5 Assessment is considered a snapshot in time. The findings and
        recommendations reflect the information gathered during the assessment and not
        any changes or modifications made outside of that period.
        </p>

        <p>Time-limited engagements do not allow for a full evaluation of all security controls.
        SQ1Security prioritized the assessment to identify the weakest security controls.
        SQ1Security recommends conducting similar assessments on an annual basis by
        External or third-party assessors to ensure the continued success of the controls.
        </p>

EOD;

        PDF::writeHTMLCell(0, 0, '', '', $page2, 0, 1, 0, true, '', true);

        /************************** SQ1Security Scani5 Assessment: Methodology ************************/

        PDF::AddPage();
        PDF::Bookmark('SQ1Security Scani5 Assessment: Methodology', 1, 0, '', 'C', [27, 117, 188]);
        PDF::Bookmark('1. Identifying the scope', 2, 0, '', 'C', [27, 117, 188]);
        PDF::Bookmark('2. Vulnerabilities detection', 2, 0, '', 'C', [27, 117, 188]);
        PDF::Bookmark('3. Controls evaluated', 2, 0, '', 'C', [27, 117, 188]);
        PDF::Bookmark('4. Vulnerable encryptions used in the scoped assets', 2, 0, '', 'C', [27, 117, 188]);
        PDF::Bookmark('5. Reporting', 2, 0, '', 'C', [27, 117, 188]);
        $page4 = <<<EOD
        $style
        <h1 style="color: #0070C0;">SQ1Security Scani5 Assessment: Methodology</h1>

        <h4 style="color: #00B0F0;">1. Identifying the scope</h4>

        <p>The assets on which Scani5 Assessment should be performed are identified
        by the client. The assets are validated and necessary access and credentials are
        obtained by SQ1Security to perform VA.
        </p>

        <h4 style="color: #00B0F0;">2. Vulnerabilities detection</h4>

        <p>For Scani5 Assessment, access to the network as well as credentials are
        required as SQ1Security will be conducting a credentialed Vulnerability
        Assessment. SQ1Security will be using an automated tool which will do a
        credentialed assessment through SMB for windows-based OS and SSH for Linux
        based OS.
        </p>

        <h4 style="color: #00B0F0;">3. Controls evaluated</h4>

        <p>Through credentialed assessments, the following controls are evaluated for
        Scani5 Assessment.
        </p>

        <ul>
        <li>Missing security patches</li>
        <li>Missing security OS updates</li>
        <li>Vulnerable configurations in the OS</li>
        <li>Vulnerable version of installed applications</li>
        <li>Vulnerable configurations of the installed applications</li>
        <li>Vulnerable encryptions used in the scoped assets</li>
        </ul>

        <h4 style="color: #00B0F0;">4. Vulnerable encryptions used in the scoped assets</h4>

        <p>The vulnerabilities identified through VA are analysed further by the team to
        weed out false positives and to validate the criticality of the identified
        vulnerabilities. The team will further provide suggestions to patch or mitigate the
        identified vulnerabilities.
        </p>

        <h4 style="color: #00B0F0;">5. Reporting</h4>

        <p>Based on the analysis, SQ1Security will provide a PDF report to the client, with
        details of the identified vulnerabilities which will include the description, severity
        and suggestions to patch or mitigate the vulnerabilities.
        </p>
EOD;
        PDF::writeHTMLCell(0, 0, '', '', $page4, 0, 1, 0, true, '', true);

        /************************ Severity Ratings ****************************/
        PDF::AddPage();
        PDF::Bookmark('Severity Ratings', 1, 0, '', 'C', [27, 117, 188]);
        $page5 = <<<EOD
        $style
        <h4 style="color: #0070C0;">Severity Ratings</h4>

        <p>The following table defines levels of severity and corresponding CVSS score range
        that are used throughout the document to assess vulnerability and risk impact.
        </p>

        <table cellpadding="8" style="border: none;">
          <tr style="color: #fff;background-color: #0070C0;">
            <th width="25%">
            <div style="vertical-align: middle;">
            Severity
            </div>
            </th>
            <th width="25%">
            CVSS V3 <br> Score Range
            </th>
            <th width="50%">
            <div style="vertical-align: middle;">
            Definition
            </div>
            </th>
          </tr>
          <tr style="background-color: #f2f2f2;">
            <td >
            <div style="vertical-align: middle;">
            <span style="color: #ff0000;font-weight: bold;">Critical</span>
            </div>
            </td>
            <td>
            <div style="vertical-align: middle;">
            9.0-10.0
            </div>
            </td>
            <td style="text-align: left;">Exploitation is straightforward and usually results in
              system-level compromise. It is advised to form a plan of
              action and patch immediately.
            </td>
          </tr>
          <tr>
            <td>
            <div style="vertical-align: middle;">
            <span style="color: #f79646;font-weight: bold;">High</span>
            </div>
            </td>
            <td>
            <div style="vertical-align: middle;">
            7.0-8.9
            </div>
            </td>
            <td style="text-align: left;">Exploitation is more difficult but could cause elevated
              privileges and potentially a loss of data or downtime. It is
              advised to form a plan of action and patch as soon as
              possible.</td>
          </tr>
          <tr style="background-color: #f2f2f2;">
            <td>
            <div style="vertical-align: middle;">
            <span style="color: #ffc000;font-weight: bold;">Medium</span>
            </div>
            </td>
            <td>
            <div style="vertical-align: middle;">
              4.0-6.9
            </div>
            </td>
            <td style="text-align: left;">Vulnerabilities exist but are not exploitable or require extra
              steps such as social engineering. It is advised to form a
              plan of action and patch after high-priority issues have
              been resolved.</td>
          </tr>
          <tr>
            <td>
            <div style="vertical-align: middle;">
            <span style="color: #00ae50;font-weight: bold;">Low</span>
            </div>
            </td>
            <td>
            <div style="vertical-align: middle;">
             0.1-3.9
            </div>
            </td>
            <td style="text-align: left;">Vulnerabilities are non-exploitable but would reduce an
              organization’s attack surface. It is advised to form a plan
              of action and patch during the next maintenance window.</td>
          </tr>
        </table>
EOD;

        PDF::writeHTMLCell(0, 0, '', '', $page5, 0, 1, 0, true, '', true);

        /************* Summary of Findings ******************/

        PDF::AddPage();
        PDF::setPrintFooter(true);
        PDF::Bookmark('Summary of Findings', 1, 0, '', 'C', [27, 117, 188]);
        $page5_1 = <<<EOD
        $style
        <h4 style="color: #0070C0;">Summary of Findings</h4>

        <p>In this assessment, all 300 hosts identified as belonging to the $this->client_name domain and
          were successfully scanned.
        </p>

        <table cellspacing="0" cellpadding="10">
            <tbody>
              <tr>
                <td style="color: #fff;background-color: #ff0000;font-weight: bold;">$critical</td>
                <td style="color: #fff;background-color: #f79646;font-weight: bold;">$high</td>
                <td style="color: #fff;background-color: #ffc000;font-weight: bold;">$medium</td>
                <td style="color: #fff;background-color: #00ae50;font-weight: bold;">$low</td>
              </tr>
              <tr>
                <td style="background-color: #f2f2f2;"><span style="color: #ff0000;font-weight: bold;">Critical</span></td>
                <td style="background-color: #f2f2f2;"><span style="color: #f79646;font-weight: bold;">High</span></td>
                <td style="background-color: #f2f2f2;"><span style="color: #ffc000;font-weight: bold;">Medium</span></td>
                <td style="background-color: #f2f2f2;"><span style="color: #00ae50;font-weight: bold;">Low</span></td>
              </tr>
            </tbody>
        </table>

        <br>

         <h4 style="color: #0070C0;">Graphical representation of findings</h4>

         <p>The table below provides a summary of the findings per severity
         </p>
         <img src="$vulnerable_chart">

EOD;
        PDF::writeHTMLCell(0, 0, '', '', $page5_1, 0, 1, 0, true, '', true);

        $vulnerability_list = '';
        $all_vulnerabilities = '';
        $i = '';

        foreach ($vulnerability as $key => $vulnerable) {
            $cve_val = json_decode($vulnerable->CVEs);

            $cves_arr = ($cve_val) ? $cve_val->cves : '';

            $cve_final = implode(',', $cves_arr);

            // Get backgroud color for Vulnerability Summary
            if ($key / 2 == 0) {
                $bg_color = 'background-color: #f2f2f2;';
            } else {
                $bg_color = 'background-color: #ffffff;';
            }

            // Get Severity
            $severity_val = $this->colorGetText($vulnerable->severity);

            $patch_priority_val = $this->colorGetText($vulnerable->patch_priority);

            $risk_priority_val = $this->colorGetNum($vulnerable->risk);

            // return $vulnerable->assetrelations;
            $asset_ips = '';
            $asset_count = [];
            $asset_heading = false;
            foreach ($vulnerable->assetrelations as $key => $vul_assets) {
                if ($type == 'asset_tags') {
                    $tag_check_asset = DB::table('taggables')->where('taggable_type', 'App\\Models\\Asset')->where('taggable_id', $vul_assets->id)->get();

                    if (count($tag_check_asset) == 0) {
                        continue;
                    }
                    array_push($asset_count, $tag_check_asset[0]->id);
                    $asset_heading = true;
                } elseif ($type == 'vulnerability_tags') {
                    $tag_check_vul = DB::table('taggables')->where('taggable_type', 'App\\Models\\Vulnerability')->where('taggable_id', $vul_assets->id)->get();

                    if (count($tag_check_vul) == 0) {
                        continue;
                    }
                    array_push($asset_count, $tag_check_vul[0]->id);
                    $asset_heading = true;
                }

                if ($key != 0) {
                    $asset_ips .= '<br>';
                }

                $asset_ips .= `<span
            style="background-color:rgb(74, 3, 140); color:#f9f9f9; padding:5px; border-radius:3px; margin-top:20px">
          ` . $vul_assets->host_name . ' ( IP Address - ' . $vul_assets->ip_address_v4 . ')' . `

            </span>`;
            }

            // Vulnerability Summary
            $vulnerability_list .= '<tr style="' . $bg_color . '">
      <td style="text-align: left;">' . $vulnerable->name . '</td>
      <td>
      <div style="vertical-align: middle;">
      <span style="' . $severity_val['font'] . '">' . ucfirst($vulnerable->severity) . '</span>
      </div>
      </td>

      </tr>';

            $all_vulnerabilities .= '<p style="color:#004cad;font-size: 16px;">' . ++$i . '. ' . $vulnerable->name . '</p>
      <div></div>

      <table cellspacing="0" cellpadding="10">
      <tbody>

      <tr>
      <td style="color:#rgb(34 33 33);font-weight: bold;"> CVEs</td>
      <td style="color:#rgb(34 33 33);font-weight: bold;"> Severity</td>
      <td style="color:#rgb(34 33 33);font-weight: bold;"> Risk</td>
      <td style="color:#rgb(34 33 33);font-weight: bold;"> Patch Priority</td>
      <td style="color:#rgb(34 33 33);font-weight: bold;"> First Seen</td>
      <td style="color:#rgb(34 33 33);font-weight: bold;"> Last Identified at</td>
    </tr>

        <tr>
        <td style="background-color: #f2f2f2;"><span style="color:#rgb(34 33 33);font-weight: bold;">' . $cve_final . '</span></td>
        <td style="background-color: #f2f2f2;"><span style="' . $severity_val['font'] . '">' . ucfirst($vulnerable->severity) . '</span></td>
        <td style="background-color: #f2f2f2;"><span style="' . $risk_priority_val['font'] . '">' . $vulnerable->risk . '</span></td>
        <td style="background-color: #f2f2f2;"><span style="' . $patch_priority_val['font'] . '">' . ucfirst($vulnerable->patch_priority) . '</span></td>
        <td style="background-color: #f2f2f2;"><span style="color:#rgb(34 33 33);font-weight: bold;">' . Carbon::parse($vulnerable->first_seen)->format('d-m-Y H:i:s') . '</span></td>
        <td style="background-color: #f2f2f2;"><span style="color:#rgb(34 33 33);font-weight: bold;">' . Carbon::parse($vulnerable->last_identified_on)->format('d-m-Y H:i:s') . '</span></td>
        </tr>


      </tbody>
  </table>

      <br>';

            if ($type == 'asset_tags' || $type == 'vulnerability_tags') {
                $all_vulnerabilities .= '<h4 style="color: #00B0F0;font-size: 15px;">Affected Hosts (' . count($asset_count) . ')</h4>';

                if ($asset_heading) {
                    $all_vulnerabilities .= '<p>Assets</p>
        <ul>' . $asset_ips . '</ul>';
                }
            } else {
                $all_vulnerabilities .= '<h4 style="color: #00B0F0;font-size: 15px;">Affected Hosts (' . count($vulnerable->assetrelations) . ')</h4>';

                if (count($vulnerable->assetrelations) > 0) {
                    $all_vulnerabilities .= '<p>Assets</p>
        <ul>' . $asset_ips . '</ul>';
                }
            }

            $all_vulnerabilities .= '<h4 style="color: #00B0F0;font-size: 15px;">Description</h4>
      <p>' . nl2br($vulnerable->description) . '</p>
      <h4 style="color: #00B0F0;font-size: 15px;">Impact</h4>
      <p>' . nl2br($vulnerable->impact) . '</p>
      <h4 style="color: #00B0F0;font-size: 15px;">Solution</h4>
      <p>' . nl2br($vulnerable->solution) . '</p>
      <h4 style="color: #00B0F0;font-size: 15px;">Workaround</h4>
      <p>' . nl2br($vulnerable->workaround) . '</p>
      <h4 style="color: #00B0F0;font-size: 15px;">Result</h4>
      <p>' . nl2br($vulnerable->result) . '</p>';
        }

        /************* Vulnerability Summary ******************/

        PDF::AddPage();
        PDF::Bookmark('Vulnerability Summary', 1, 0, '', 'C', [27, 117, 188]);
        $page5_2 = <<<EOD
    $style
    <h4 style="color: #0070C0;">Vulnerability Summary</h4>
    <table cellpadding="8">
      <tr style="background-color: #0070c0;color: #fff;">
        <th width="75%">Finding</th>
        <th width="25%">Severity</th>
      </tr>
      <tr style="background-color: #dbe5f1;">
        <th>Scani5 Assessment findings</th>
        <th></th>
      </tr>
      $vulnerability_list
  </table>
  <br>
EOD;
        PDF::writeHTMLCell(0, 0, '', '', $page5_2, 0, 1, 0, true, '', true);

        /************* Technical Findings ******************/

        PDF::AddPage();
        PDF::Bookmark('Technical Findings', 1, 0, '', 'C', [27, 117, 188]);

        $last = <<<EOD
    $style
    <h1 style="color: #0070C0;">Technical Findings</h1>
EOD;

        PDF::writeHTMLCell(0, 0, '', '', $last . $all_vulnerabilities, 0, 1, 0, true, '', true);
        PDF::lastPage();

        /************************** Table of Contents ************************/
        PDF::addTOCPage('P', 'A4');
        PDF::SetTextColor(27, 117, 188);
        PDF::SetFont('helvetica', 'B', 14);
        PDF::MultiCell(0, 0, 'Table Of Contents', 0, 'C', 0, 1, '', '', true, 0);
        PDF::Ln();
        PDF::addTOC(2, '', '.', 'Table Of contents', 'B', [27, 117, 188]);
        PDF::endTOCPage();

        $fileName = "scani5_{$reqname}_{$scan_date}.pdf";
        // $fileName = 'scani5_' . $reqname . "_" . $scan_date . '.pdf';

        $report_path = ReportModel::where('organization_id')->pluck('path')->first();
        if (!$report_path) {
            $org_path = folderFindOrCreate($orgId);
            $report_path = createReportFolder($org_path);
        }

        $filePath = $report_path . '/' . $fileName;

        $fullPath = storage_path('app/public/' . $filePath);

        PDF::Output($fullPath, 'F');

        $report_model = new ReportModel();
        $report_model->organization_id = $orgId;
        $report_model->name = $fileName;
        $report_model->path = $report_path;
        $report_model->created_by = auth()->user()->id;
        $report_model->save();
        // Prepare JSON response
        $response = [
            'pdf_base64' => base64_encode(PDF::Output($fullPath, 'S')),
            'filename' => $fileName
        ];

        LogHelper::logAction('Generated', 'Report', 'User generated the report', getRoleId(), $orgId);
        return $this->sendResponse($response, 'Report created successfully');
    }

    /***************** Styles *********************/

    private function getStyle()
    {
        $style = '
 <style>
     h1 {
         font-size:20px;
         color:#ef3e41;
         font-weight:bold;
     }

     h2 {
         font-size:40px;
         color:#ef3e41;
         font-weight:bold;
         text-transform:uppercase;
         letter-spacing:1px;
         line-height:20px;
     }

     h4 {
         font-size:16px;
         color : #1B75BC;
         font-weight:bold;
     }

     p,
     ul > li {
         font-size:14px;
         line-height:20px;
         color:#000;
         font-weight:normal;
         text-align: justify;
     }

     p > strong{
         font-size:13px;
         color:#65666d;
         font-weight:normal;
     }

     table {
          border: solid 1px #cacbd8;
          border-collapse: collapse;
          border-spacing: 0;
          color:#000;
          font-weight:normal;
          line-height:28px;
          text-align:center;
     }

     th {
         font-weight : bold;
         border: solid 1px #cacbd8;
         font-size:12px;
     }


     td {
          font-size:12px;
          border: solid 1px #cacbd8;
          padding:20px;
     }

     .high {
         background:red;
     }

     .count-size {
       font-size:20px;
       color:white;
       padding-bottom:50px;
       text-align:left;
       margin-left:20px;
     }


     ul.b {
       display : flex;
       list-style-type: square;
     }

 </style>
 ';

        return $style;
    }

    private function getSeverity($query)
    {
        $critical = $query->where('severity', 'critical')->count();
        $high = $query->where('severity', 'high')->count();
        $medium = $query->where('severity', 'medium')->count();
        $low = $query->where('severity', 'low')->count();

        $data = [
            max(0, $critical),
            max(0, $high),
            max(0, $medium),
            max(0, $low),
        ];

        return $url = 'https://quickchart.io/chart?bkg=%23fff&c=' . urlencode(json_encode([
            'type' => 'pie',
            'data' => [
                'datasets' => [
                    [
                        'data' => $data,
                        'backgroundColor' => [
                            'rgb(255, 99, 132)',
                            'rgb(255, 159, 64)',
                            'rgb(255, 205, 86)',
                            'rgb(75, 192, 192)'
                        ],
                        'label' => 'Dataset 1'
                    ]
                ],
                'labels' => ['Critical', 'High', 'Medium', 'Low']
            ]
        ]));
    }

    private function colorGetText($value)
    {
        $bg = '';
        $font = '';
        switch (ucfirst($value)) {
            case 'Critical':
                $bg = 'color: #fff;background-color: #ff0000;font-weight: bold;';
                $font = 'color: #ff0000;font-weight: bold;';
                break;

            case 'High':
                $bg = 'color: #fff;background-color: #f79646;font-weight: bold;';
                $font = 'color: #f79646;font-weight: bold;';
                break;

            case 'Medium':
                $bg = 'color: #fff;background-color: #ffc000;font-weight: bold;';
                $font = 'color: #ffc000;font-weight: bold;';
                break;

            case 'Low':
                $bg = 'color: #fff;background-color: #00ae50;font-weight: bold;';
                $font = 'color: #00ae50;font-weight: bold;';
                break;
        }

        return ['bg' => $bg, 'font' => $font];
    }

    private function colorGetNum($value)
    {
        $bg = '';
        $font = '';
        switch ($value) {
            case ($value >= 9 && $value <= 10):
                $bg = 'color: #fff;background-color: #ff0000;font-weight: bold;';
                $font = 'color: #ff0000;font-weight: bold;';
                break;

            case ($value >= 7 && $value <= 8):
                $bg = 'color: #fff;background-color: #f79646;font-weight: bold;';
                $font = 'color: #f79646;font-weight: bold;';
                break;

            case ($value >= 4 && $value <= 6):
                $bg = 'color: #fff;background-color: #ffc000;font-weight: bold;';
                $font = 'color: #ffc000;font-weight: bold;';
                break;

            case ($value >= 0 && $value <= 3):
                $bg = 'color: #fff;background-color: #00ae50;font-weight: bold;';
                $font = 'color: #00ae50;font-weight: bold;';
                break;
        }

        return ['bg' => $bg, 'font' => $font];
    }

    /**
     * Delete report
     */
    public function destroy($orgId, $id)
    {
        $report = ReportModel::find($id);
        if (!$report) {
            return $this->sendError('File not found', null, 404);
        }

        $fileName = $report->name;

        $filePath = $report->path . '/' . $fileName;
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
            $report->delete();

            LogHelper::logAction('Deleted', 'Report', "User deleted the '{$fileName}' report ", getRoleId(), $orgId);

            return $this->sendResponse(null, 'Report deleted successfully');
        } else {
            return $this->sendError('Something went wrong. Please try again later');
        }
    }
}
