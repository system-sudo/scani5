<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\RoleEnum;
use App\Models\Asset;
use App\Models\Exploits;
use App\Models\OrganizationModel;
use App\Models\Patch;
use App\Models\Vulnerability;
use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Mandatory Seeders !(do not remove)
        $this->call(RoleSeeder::class);
        $this->call(AdminSeeder::class);

        OrganizationModel::factory(5)
        ->hasAttached(User::factory()->count(1), ['role_id' => RoleNameOrId(null, RoleEnum::OrgSuperAdmin)])
        // ->hasAttached(User::find(getSuperAdminId()), ['role_id' => RoleNameOrId(null, RoleEnum::OrgSuperAdmin), 'user_id' => getSuperAdminId()])
        ->has(Asset::factory()->count(20)
        ->hasAttached(Vulnerability::factory()->count(5)
        ->has(Exploits::factory()->count(2))
        ->has(Patch::factory()->count(2))))
        ->create();
        // $this->call(MappingSeeder::class);
    }
}
