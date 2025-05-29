<?php

namespace Tests\Feature\Imports;

use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class ImportsTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/csvimports/';

    /**
     * @dataProvider csvImportProvider
     */
    public function test_csv_imports(string $importType): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . $importType);

        $expectedMessage = ucfirst($importType) . ' Import successful';

        $resp->assertOk()->assertJson(['message' => $expectedMessage]);
    }

    public static function csvImportProvider(): array
    {
        return [
            'Assets Import' => ['assets'],
            'Vulnerabilities Import' => ['vulnerability'],
            'Patches Import' => ['patch'],
            'Exploits Import' => ['exploits'],
        ];
    }
}
