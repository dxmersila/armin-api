<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_free_item(): void
    {
        $item = Item::factory()->create([
            'access_type' => 'free',
        ]);

        $response = $this->getJson("/api/v1/items/{$item->slug}/access");

        $response->assertUnauthorized();
    }

    public function test_guest_cannot_access_paid_item(): void
    {
        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $response = $this->getJson("/api/v1/items/{$item->slug}/access");

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_free_item(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'free',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/v1/items/{$item->slug}/access");

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Item access granted.',
            ]);
    }

    public function test_authenticated_user_can_access_paid_item_with_individual_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        Access::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/v1/items/{$item->slug}/access");

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Item access granted.',
            ]);
    }

    public function test_authenticated_user_cannot_access_paid_item_without_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/v1/items/{$item->slug}/access");

        $response->assertForbidden();
    }
}
