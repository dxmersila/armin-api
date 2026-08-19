<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_free_item_content(): void
    {
        $item = Item::factory()->create([
            'access_type' => 'free',
            'content' => 'Free item secret content',
        ]);

        $response = $this->getJson(
            "/api/v1/items/{$item->slug}/content"
        );

        $response->assertUnauthorized();
    }

    public function test_guest_cannot_access_paid_item_content(): void
    {
        $item = Item::factory()->create([
            'access_type' => 'paid',
            'content' => 'Paid item secret content',
        ]);

        $response = $this->getJson(
            "/api/v1/items/{$item->slug}/content"
        );

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_free_item_content(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'free',
            'content' => 'Free item secret content',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson("/api/v1/items/{$item->slug}/content");

        $response
            ->assertOk()
            ->assertJson([
                'content' => 'Free item secret content',
            ]);
    }

    public function test_authenticated_user_can_access_paid_item_content_with_individual_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'content' => 'Paid item secret content',
        ]);

        Access::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson("/api/v1/items/{$item->slug}/content");

        $response
            ->assertOk()
            ->assertJson([
                'content' => 'Paid item secret content',
            ]);
    }

    public function test_authenticated_user_cannot_access_paid_item_content_without_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'content' => 'Paid item secret content',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson("/api/v1/items/{$item->slug}/content");

        $response->assertForbidden();
    }

    public function test_hidden_item_content_returns_not_found(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'is_visible' => false,
            'access_type' => 'free',
            'content' => 'Hidden item secret content',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson("/api/v1/items/{$item->slug}/content");

        $response->assertNotFound();
    }

    public function test_non_existing_item_content_returns_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/api/v1/items/non-existing-item/content');

        $response->assertNotFound();
    }

    public function test_public_item_endpoint_does_not_expose_content(): void
    {
        $item = Item::factory()->create([
            'access_type' => 'free',
            'content' => 'SECRET CONTENT',
        ]);

        $response = $this->getJson(
            "/api/v1/items/{$item->slug}"
        );

        $response
            ->assertOk()
            ->assertJsonMissing([
                'content' => 'SECRET CONTENT',
            ]);
    }
}
