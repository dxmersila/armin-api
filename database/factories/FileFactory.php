<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['jpg', 'png', 'webp', 'pdf']);

        return [
            'name' => fake()->word() . '.' . $extension,
            'path' => 'files/' . fake()->uuid() . '.' . $extension,
            'mime_type' => match ($extension) {
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'pdf' => 'application/pdf',
            },
            'size' => fake()->numberBetween(10_000, 5_000_000),
        ];
    }
}
