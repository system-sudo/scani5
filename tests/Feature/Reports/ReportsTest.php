<?php

namespace Tests\Feature\Reports;

use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/reports/';

    public function test_get_reports(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl, ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Reports displayed successfully.']);
    }

    public function test_reports_cards(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'count', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Report cards displayed successfully']);
    }

    public function test_get_ip(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'ip', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Ip address shown successfully']);
    }

    public function test_generate_report(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'generate', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Report created successfully']);
    }

    public function test_delete_report(): void
    {
        $resp = $this->apiRequest('delete', $this->baseUrl . 'delete-report/'.self::$orgId.'/5');

        $resp->assertOk()->assertJson(['message' => 'Report deleted successfully']);
    }

    public function test_report_download(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'download', ['orgId' => self::$orgId, 'id' => '5']);

        $resp->assertOk()->assertJson(['message' => 'Downloaded successfully']);
    }
}
