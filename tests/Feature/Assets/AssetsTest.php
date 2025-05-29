<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class AssetsTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/assets/';
    protected $assetId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assetId = Asset::first()?->id ?? 1;
    }

    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_get_single_asset_details(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'details/' . $this->assetId);

        $resp->assertOk()->assertJson(['message' => 'Asset details displayed successfully.']);
    }

    public function test_get_all_assets(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl, ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Assets displayed successfully.']);
    }

    public function test_export_vulnerability_assets(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'export-vulnerability-assets', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Assets exported successfully.']);
    }

    public function test_report_asset(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'report', ['comment' => 'This asset has some hardware issues', 'id' => $this->assetId, 'orgId' => 2]);

        $resp->assertOk()->assertJson(['message' => 'Asset reported successfully.']);
    }

    public function test_retire_asset(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'retire', ['orgId' => self::$orgId, 'id' => $this->assetId]);

        $resp->assertOk()->assertJson(['message' => 'Asset deleted successfully.']);
    }

    public function test_assets_export(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'export', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Assets exported successfully.']);
    }

    public function test_assets_count(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'count', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Assets count displayed successfully.']);
    }
}
