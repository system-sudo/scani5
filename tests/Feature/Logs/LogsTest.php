<?php

namespace Tests\Feature\Logs;

use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class LogsTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/logs/';

    public function test_get_all_logs(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl);

        $resp->assertOk()->assertJson(['message' => 'Logs displayed successfully']);
    }

    public function test_get_logs_cards(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'count');

        $resp->assertOk()->assertJson(['message' => 'Logs cards displayed successfully.']);
    }
}
