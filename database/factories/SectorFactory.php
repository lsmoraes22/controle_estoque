<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Sector> */
class SectorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sector' => 'Demo '.fake()->unique()->numerify('######'),
            'enabled' => true,
        ];
    }
}
