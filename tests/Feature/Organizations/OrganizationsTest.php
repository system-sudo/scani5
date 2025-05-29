<?php

namespace Tests\Feature\Organizations;

use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class OrganizationsTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/organizations/';

    public function test_switch_org(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'switch');

        $resp->assertOk()->assertJson(['message' => 'Organization listed successfully.']);
    }

    public function test_show_assigned(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'assign');

        $resp->assertOk()->assertJson(['message' => 'Organization listed successfully.']);
    }

    public function test_roles(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'roles', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Roles displayed successfully.']);
    }

    public function test_user_organizations(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'user-organizations/' . self::$loggedInUser);

        $resp->assertOk()->assertJson(['message' => 'organization displayed successfully']);
    }

    public function test_user_organizations_count(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'count/user-organizations', ['userId' => self::$loggedInUser]);

        $resp->assertOk()->assertJson(['message' => 'Organization cards displayed successfully.']);
    }

    public function test_invite(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'invite', ['email' => 'udhayakumar.g@sq1.security', 'role_id' => 1, 'route' => 'invite', 'orgId' => '5', 'name' => 'newOrg']);

        $resp->assertOk()->assertJson(['message' => 'User created successfully and invite link has been sent to E-mail.']);
    }

    public function test_re_invite(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'reinvite', ['email' => 'udhayakumar.g@sq1.security', 'route' => 'invite', 'orgId' => '5']);

        $resp->assertOk()->assertJson(['message' => 'Invitation has been resent successfully.']);
    }

    public function test_filter_roles(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'filter-roles', ['type' => 'admin']);

        $resp->assertOk()->assertJson(['message' => 'Roles displayed successfully.']);
    }

    public function test_get_organizations(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl);

        $resp->assertOk()->assertJson(['message' => 'Organization displayed successfully.']);
    }

    public function test_get_organizations_count(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'count');

        $resp->assertOk()->assertJson(['message' => 'Organization cards displayed successfully.']);
    }

    public function test_edit_role(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'edit-role', ['orgId' => '5', 'role_id' => 1, 'userId' => self::$loggedInUser]);

        $resp->assertOk()->assertJson(['message' => 'User role changed successfully']);
    }

    public function test_organizations_info_update(): void
    {
        $resp = $this->apiRequest('put', $this->baseUrl . 'info/' . self::$orgId);

        $resp->assertOk()->assertJson(['message' => 'Organization updated successfully.']);
    }

    public function test_enable_disable(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'org-status', ['status' => 'active', 'id' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Status changed successfully.']);
    }

    // public function test_org_destroy(): void
    // {
    //     $resp = $this->apiRequest('delete', $this->baseUrl . 'delete/' . self::$orgId);

    //     $resp->assertOk()->assertJson(['message' => 'Organization deleted successfully']);
    // }

    public function test_edit_org(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'edit/' . self::$orgId);

        $resp->assertOk()->assertJson(['message' => 'Organization showed successfully.']);
    }

    public function test_update_org(): void
    {
        $resp = $this->apiRequest('put', $this->baseUrl . 'update/' . self::$orgId);

        $resp->assertOk()->assertJson(['message' => 'Updated successfully.']);
    }
    // public function test_org_cards(): void
    // {
    //     $resp = $this->apiRequest('get', $this->baseUrl . 'cards/' . self::$orgId);

    //     $resp->assertOk()->assertJson(['message' => 'Updated successfully.']);
    // }
    public function test_org_logo(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'get-org-logo', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'Organization logo shown successfully']);
    }

    public function test_organizations_info(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'info/' . self::$orgId);

        $resp->assertOk()->assertJson(['message' => 'Organization displayed successfully']);
    }

    public function test_verify_link(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'verify-link/rerewrw');

        $resp->assertOk()->assertJson(['message' => 'Success']);
    }
    public function test_register(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'register');

        $resp->assertOk()->assertJson(['message' => 'Created successfully.']);
    }
}
