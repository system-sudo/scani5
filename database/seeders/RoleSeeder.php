<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\RoleModel;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $row = [
            RoleEnum::SuperAdmin,
            RoleEnum::Admin,
            RoleEnum::User,
            RoleEnum::OrgSuperAdmin,
            RoleEnum::OrgAdmin,
            RoleEnum::OrgUser,
        ];

        for ($i = 0; $i < count($row); $i++) {
            RoleModel::create([
                'name' => $row[$i],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
