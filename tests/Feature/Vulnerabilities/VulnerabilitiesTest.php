<?php

namespace Tests\Feature\Vulnerabilities;

use App\Models\Asset;
use App\Models\Exploits;
use App\Models\Vulnerability;
use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class VulnerabilitiesTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/vulnerabilities/';
    protected $vulId;
    protected $assetId;
    protected $exploitId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vulId = Vulnerability::first()?->id ?? 1;
        $this->assetId = Asset::first()?->id ?? 1;
        $this->exploitId = Exploits::first()?->id ?? 1;
    }

    public function test_get_vulnerabilities(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl, ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Vulnerabilities displayed successfully.']);
    }

    public function test_get_single_vulnerability_details(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'details/' . $this->vulId, ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Vulnerability details retrieved successfully.']);
    }

    public function test_get_vulnerability_assets(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'assets/' . $this->vulId, ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Assets displayed successfully.']);
    }

    public function test_vulnerabilities_count(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'count', ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Vulnerability cards displayed successfully.']);
    }

    public function test_get_asset_vulnerabilities(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'asset-vulnerabilities/' . $this->vulId, ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Vulnerabilities displayed successfully.']);
    }

    public function test_export_vulnerabilities(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'export', ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Vulnerabilities exported successfully']);
    }

    public function test_get_patch(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'patch/' . $this->vulId, ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Patch displayed successfully.']);
    }

    public function test_get_exploit(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'exploit/' . $this->assetId, ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Exploits displayed successfully.']);
    }

    public function test_get_vulnerability_exploit(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'vulnerability-exploits/' . $this->vulId, ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Exploits displayed successfully.']);
    }

    public function test_get_exploit_vulnerability(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'exploits-vulnerability/' . $this->exploitId, ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Vulnerabilities displayed successfully.']);
    }

    public function test_total_exploits(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'total-exploits', ['orgId' => 1]);

        $resp->assertOk()->assertJson(['message' => 'Exploits displayed successfully.']);
    }
}
