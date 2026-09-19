<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\File;
use App\Models\Item;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        $categories = collect([
            ['name' => 'AI Prompts', 'slug' => 'ai-prompts'],
            ['name' => 'Design', 'slug' => 'design'],
            ['name' => 'Development', 'slug' => 'development'],
        ])->map(fn (array $data) => Category::create($data));

        $items = [
            [
                'title' => 'Free AI Starter Prompt',
                'slug' => 'free-ai-starter-prompt',
                'description' => 'A free starter prompt for testing the public product flow.',
                'is_visible' => true,
                'access_type' => 'free',
                'price' => 0,
            ],
            [
                'title' => 'Premium Content Pack',
                'slug' => 'premium-content-pack',
                'description' => 'A premium digital product for testing checkout and access.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 190000,
            ],
            [
                'title' => 'Advanced Prompt Collection',
                'slug' => 'advanced-prompt-collection',
                'description' => 'An advanced paid product for testing the marketplace.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 290000,
            ],
            [
                'title' => 'Hidden Test Product',
                'slug' => 'hidden-test-product',
                'description' => 'A hidden product for testing visibility rules.',
                'is_visible' => false,
                'access_type' => 'paid',
                'price' => 99000,
            ],
        ];

        foreach ($items as $index => $data) {
            $item = Item::create($data);

            $item->categories()->attach(
                $categories[$index % $categories->count()]->id
            );

            $file = File::factory()->create();

            Media::create([
                'item_id' => $item->id,
                'file_id' => $file->id,
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }
    }
}
