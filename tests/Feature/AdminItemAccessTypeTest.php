<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemAccessTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_item_with_access_type(): void
    {
        $response = $this->postJson('/api/v1/admin/items', [
            'title' => 'Test Item',
            'access_type' => 'paid',
        ]);

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_create_item_with_access_type(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Test Item',
                'access_type' => 'paid',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_free_item(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Free Item',
                'access_type' => 'free',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.access_type', 'free');

        $this->assertDatabaseHas('items', [
            'title' => 'Free Item',
            'access_type' => 'free',
        ]);
    }

    public function test_admin_can_create_paid_item(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Paid Item',
                'access_type' => 'paid',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.access_type', 'paid');

        $this->assertDatabaseHas('items', [
            'title' => 'Paid Item',
            'access_type' => 'paid',
        ]);
    }

    public function test_access_type_defaults_to_free(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Default Access Item',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.access_type', 'free');

        $this->assertDatabaseHas('items', [
            'title' => 'Default Access Item',
            'access_type' => 'free',
        ]);
    }

    public function test_invalid_access_type_is_rejected(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Invalid Item',
                'access_type' => 'premium',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'access_type',
            ]);
    }

    public function test_admin_can_update_access_type(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create([
            'access_type' => 'free',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/items/{$item->id}",
                [
                    'access_type' => 'paid',
                ]
            );

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.access_type', 'paid');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'access_type' => 'paid',
        ]);
    }

    public function test_invalid_access_type_is_rejected_on_update(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create([
            'access_type' => 'free',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/items/{$item->id}",
                [
                    'access_type' => 'premium',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'access_type',
            ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'access_type' => 'free',
        ]);
    }
}
