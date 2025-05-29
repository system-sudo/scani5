<?php

namespace Database\Factories;

use App\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationModelFactory extends Factory
{
    // protected $model = OrganizationModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'short_name' => $this->faker->word,
            // 'dark_logo' => $this->faker->imageUrl(),
            // 'folder_path' => $this->faker->filePath(),
        ];
    }
}
