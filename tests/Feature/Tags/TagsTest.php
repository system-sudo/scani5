<?php

namespace Tests\Feature\Tags;

use App\Models\Asset;
use App\Models\Tag;
use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class TagsTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/tags/';
    protected $tagId;
    protected $assetId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tagId = Tag::first()?->id ?? 1;
        $this->assetId = Asset::first()?->id ?? 1;
    }

    public function test_get_tags(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl, ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Tags displayed successfully']);
    }

    public function test_tags_cards(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'count', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Tag cards displayed successfully']);
    }

    public function test_delete_tags(): void
    {
        $resp = $this->apiRequest('delete', $this->baseUrl . 'all', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'All tags cleared successfully']);
    }

    public function test_report_tags(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'report-tags', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Tags displayed successfully']);
    }

    // public function test_delete_asset_tag(): void
    // {
    //     $resp = $this->apiRequest('post', $this->baseUrl . 'delete-asset-tag', ['orgId' => self::$orgId]);

    //     $resp->assertOk()->assertJson(['message' => 'Tags cleared successfully']);
    // }
    public function test_edit_tag(): void
    {
        $resp = $this->apiRequest('put', $this->baseUrl . 'update/' . $this->tagId, ['orgId' => self::$orgId, 'name' => 'tes']);

        $resp->assertOk()->assertJson(['message' => 'Tag updated successfully']);
    }

    public function test_delete_tag(): void
    {
        $resp = $this->apiRequest('delete', $this->baseUrl . self::$orgId . '/' . $this->tagId);

        $resp->assertOk()->assertJson(['message' => 'Tag deleted successfully']);
    }

    public function test_add_tag(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl, ['orgId' => self::$orgId, 'name' => 'testtag']);

        $resp->assertOk()->assertJson(['message' => 'Tag added successfully']);
    }

    public function test_assign_tag(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'assign', ['orgId' => self::$orgId, 'type' => 'asset', 'assetId' => $this->assetId]);

        $resp->assertOk()->assertJson(['message' => 'Tag assigned successfully']);
    }
}
