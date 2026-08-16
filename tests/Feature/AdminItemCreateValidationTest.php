<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemCreateValidationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_title_is_required(): void
    {
        $response = $this
            ->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/items', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_title_cannot_exceed_255_characters(): void
    {
        $response = $this
            ->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => str_repeat('a', 256),
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_slug_must_be_unique(): void
    {
        $admin = $this->admin();

        \App\Models\Item::factory()->create([
            'slug' => 'existing-item',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Another Item',
                'slug' => 'existing-item',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_categories_must_be_an_array(): void
    {
        $response = $this
            ->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Test Item',
                'categories' => 'invalid',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['categories']);
    }

    public function test_category_ids_must_exist(): void
    {
        $response = $this
            ->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Test Item',
                'categories' => [999999],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['categories.0']);
    }

    public function test_tags_must_be_an_array(): void
    {
        $response = $this
            ->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Test Item',
                'tags' => 'invalid',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    }

    public function test_tag_ids_must_exist(): void
    {
        $response = $this
            ->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Test Item',
                'tags' => [999999],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tags.0']);
    }

    public function test_is_visible_must_be_boolean(): void
    {
        $response = $this
            ->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Test Item',
                'is_visible' => 'not-a-boolean',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_visible']);
    }

    public function test_valid_categories_and_tags_are_accepted(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this
            ->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Test Item',
                'categories' => [$category->id],
                'tags' => [$tag->id],
            ]);

        $response->assertStatus(201);
    }
}
