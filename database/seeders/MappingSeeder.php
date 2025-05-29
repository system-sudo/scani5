<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 200 ; $i++) {
            DB::table(('vulnerables'))->insert([
                'asset_id' => $i,
                'vulnerability_id' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
