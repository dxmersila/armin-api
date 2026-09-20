<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

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

    public function test_authenticated_user_can_access_free_item_content_after_claiming(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'free',
            'content' => 'Free item secret content',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/v1/items/{$item->slug}/access")
            ->assertOk();

        $response = $this
            ->actingAs($user, 'sanctum')
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

    public function test_authenticated_user_can_access_paid_item_content_with_active_subscription(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'content' => 'Subscription protected content',
        ]);

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'order_id' => $order->id,
            'starts_at' => '2026-08-27 12:00:00',
            'ends_at' => '2026-11-27 12:00:00',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson("/api/v1/items/{$item->slug}/content");

        $response
            ->assertOk()
            ->assertJson([
                'content' => 'Subscription protected content',
            ]);
    }

    public function test_expired_subscription_does_not_grant_paid_item_content_access(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'content' => 'Subscription protected content',
        ]);

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'order_id' => $order->id,
            'starts_at' => '2026-07-01 12:00:00',
            'ends_at' => '2026-08-01 12:00:00',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson("/api/v1/items/{$item->slug}/content");

        $response->assertForbidden();
    }
}
