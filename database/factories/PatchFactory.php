<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patch>
 */
class PatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vul_id' => \App\Models\Vulnerability::factory(), // Random vulnerabilities id
            'solution' => $this->faker->sentence(), // Random solution string
            'description' => $this->faker->paragraph(), // Random description
            'complexity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']), // Random complexity
            'type' => $this->faker->randomElement(['Buffer overflow',  'Xss', 'SQL Injection', 'Directory Traversal']), // Random type
            'url' => $this->faker->url(), // Random URL
            'os' => $this->faker->randomElement(['Windows 11', 'Linux 21.3', 'Linux Mint 21.3', 'Windows 11 21H2', 'Windows 11 22H2', 'Windows 11 23H2']), //Random os
            'status' => $this->faker->randomElement(['0','1', '2']), // Random complexity
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
