<?php

namespace Tests\Feature\Users;

use Tests\Feature\Traits\ApiRequestTrait;
use Tests\TestCase;

class UsersTest extends TestCase
{
    // use ApiRequestTrait;
    protected $baseUrl = '/api/users/';

    public function test_get_users(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl);

        $resp->assertOk()->assertJson(['message' => 'Users displayed successfully.']);
    }

    public function test_get_users_count(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'count', ['orgId' => self::$orgId]);

        $resp->assertOk()->assertJson(['message' => 'User cards displayed successfully.']);
    }

    public function test_show_user(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . self::$orgId);

        $resp->assertOk()->assertJson(['message' => 'User profile displayed successfully.']);
    }

    public function test_update_user(): void
    {
        $resp = $this->apiRequest('put', $this->baseUrl . self::$loggedInUser, ['name' => 'Testtt']);

        $resp->assertOk()->assertJson(['message' => 'Profile updated successfully.']);
    }

    public function test_change_password(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'change-password', ['user_id' => self::$loggedInUser, 'old_password' => 'pass', 'new_password' => 'test', 'confirm_password' => 'gfg']);

        $resp->assertOk()->assertJson(['message' => 'Password changed Successfully.']);
    }

    public function test_get_organization_users(): void
    {
        $resp = $this->apiRequest('get', $this->baseUrl . 'organization-users/' . self::$orgId);

        $resp->assertOk()->assertJson(['message' => 'Organization users displayed successfully.']);
    }

    public function test_unlock_user(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'unlock', ['user_id' => self::$loggedInUser]);

        $resp->assertOk()->assertJson(['message' => 'User unlocked successfully']);
    }

    public function test_delete_organization_user(): void
    {
        $resp = $this->apiRequest('delete', $this->baseUrl . 'organizations-user/' . self::$orgId . '/' . self::$loggedInUser);

        $resp->assertOk()->assertJson(['message' => 'User deleted successfully.']);
    }

    public function test_destroy_user(): void
    {
        $resp = $this->apiRequest('delete', $this->baseUrl . self::$orgId . '/' . self::$loggedInUser);

        $resp->assertOk()->assertJson(['message' => 'User deleted successfully.']);
    }

    public function test_assign_organizations(): void
    {
        $resp = $this->apiRequest('post', $this->baseUrl . 'assign-organizations', ['orgId' => self::$orgId, 'role_id' => '1', 'user_id' => self::$loggedInUser]);
        $org_name = getOrgName(1);

        $resp->assertOk()->assertJson(['message' => "User assigned to '{$org_name}' organization succesfully"]);
    }

    public function test_user_organization(): void
    {
        $resp = $this->apiRequest('delete', $this->baseUrl . 'user-organization' . self::$orgId . '/' . self::$loggedInUser);

        $resp->assertOk()->assertJson(['message' => 'User deleted successfully']);
    }
}
