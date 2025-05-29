<?php

namespace Tests\Feature\Dashboard;

use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/dashboard/';

    public function test_admin_dashboard(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'admin');

        $resp->assertOk()->assertJson(['message' => 'Dashboard loaded successfully.']);
    }

    public function test_org_details_basic(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'organization', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Dashboard loaded successfully.']);
    }

    public function test_risk_distribution(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'risk-distribution', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Dashboard loaded successfully.']);
    }

    public function test_age_matrix(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'age-matrix', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Dashboard loaded successfully.']);
    }

    public function test_organization_status_chart(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'status-charts', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Dashboard loaded successfully.']);
    }

    public function test_scanify_score(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'scanify-score', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Dashboard loaded successfully.']);
    }
}
