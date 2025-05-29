<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\OrganizationModel;
use App\Models\UserRoleOrgModel;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $org = OrganizationModel::create([
        //     'name' => 'SQ1 Security',
        //     'status' => 'active',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        $sq1_admin = User::create([
            'name' => 'Admin',
            'email' => 'sq1admin@secqureone.com',
            'password' => Hash::make('Dev@sq1scani5'),
            'user_status' => 'invited',
            'is_locked' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // $sq1_admin->assignRole(RoleEnum::SuperAdmin, $sq1_admin->id);
        
      
        UserRoleOrgModel::create([
            'user_id' => $sq1_admin->id,
            'organization_id' => NULL,
            'role_id' => RoleNameOrId(null, RoleEnum::SuperAdmin),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
