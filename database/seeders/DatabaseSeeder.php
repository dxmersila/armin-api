<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\File;
use App\Models\Item;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(5)->create();

        $categories = Category::factory(5)->create();
        $tags = Tag::factory(10)->create();
        $items = Item::factory(20)->create();

        $items->each(function (Item $item) use ($categories, $tags) {
            $item->categories()->attach(
                $categories->random(rand(1, 2))
            );

            $item->tags()->attach(
                $tags->random(rand(1, 3))
            );

            $mediaCount = rand(1, 3);

            for ($i = 0; $i < $mediaCount; $i++) {
                $file = File::factory()->create();

                Media::factory()->create([
                    'item_id' => $item->id,
                    'file_id' => $file->id,
                    'is_primary' => $i === 0,
                    'sort_order' => $i,
                ]);
            }
        });
    }
}
