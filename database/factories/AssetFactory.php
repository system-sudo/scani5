<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\OrganizationModel;

class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Asset::class;
    public function definition(): array
    {
        return [
            'organization_id' => OrganizationModel::factory(),
            'host_id' => $this->faker->numberBetween(10000000, 99999999),
            'host_name' => $this->faker->domainName,
            'resource_id' => $this->faker->numberBetween(10000, 99999),
            'ip_address_v4' => $this->faker->ipv4,
            'ip_address_v6' => $this->faker->ipv6,
            'os' => $this->faker->randomElement(['Windows', 'Linux', 'Mac']),
            'rti_score' => $this->faker->numberBetween(0, 10),
            // 'comment' => $this->faker->sentence,
            'severity' => $this->faker->randomElement(['critical', 'high','medium','low']),
            'agent_status' => $this->faker->randomElement(['connected', 'disconnected']),
            'type' => $this->faker->randomElement(['workstation', 'server']),
            'last_scanned' => $this->faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d H:i:s'),
            'last_user_login' =>  null,
            'last_system_boot' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d H:i:s'),
            'last_checked_in' => $this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d H:i:s'),
        ];
    }
}
