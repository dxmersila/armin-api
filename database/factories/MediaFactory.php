<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Item;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'file_id' => File::factory(),
            'is_primary' => false,
            'sort_order' => 0,
        ];
    }
}
