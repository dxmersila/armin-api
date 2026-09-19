<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Models\Access;
use App\Models\Item;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_library(): void
    {
        $response = $this->getJson('/api/v1/library');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_see_visible_free_items_in_library(): void
    {
        $user = User::factory()->create();

        $freeItem = Item::factory()->create([
            'title' => 'Free Item',
            'access_type' => 'free',
            'is_visible' => true,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/library');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $freeItem->id,
                'title' => 'Free Item',
            ]);
    }

    public function test_authenticated_user_can_see_paid_item_with_direct_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'title' => 'Purchased Item',
            'access_type' => 'paid',
            'is_visible' => true,
        ]);

        Access::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/library');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $item->id,
                'title' => 'Purchased Item',
            ]);
    }

    public function test_paid_item_without_access_is_not_in_library(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'title' => 'Locked Item',
            'access_type' => 'paid',
            'is_visible' => true,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/library');

        $response
            ->assertOk()
            ->assertJsonMissing([
                'id' => $item->id,
                'title' => 'Locked Item',
            ]);
    }

    public function test_active_subscription_grants_all_visible_paid_items(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $firstItem = Item::factory()->create([
            'title' => 'First Paid Item',
            'access_type' => 'paid',
            'is_visible' => true,
        ]);

        $secondItem = Item::factory()->create([
            'title' => 'Second Paid Item',
            'access_type' => 'paid',
            'is_visible' => true,
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
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/library');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $firstItem->id,
                'title' => 'First Paid Item',
            ])
            ->assertJsonFragment([
                'id' => $secondItem->id,
                'title' => 'Second Paid Item',
            ]);
    }

    public function test_expired_subscription_does_not_grant_paid_item_access(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $item = Item::factory()->create([
            'title' => 'Expired Subscription Item',
            'access_type' => 'paid',
            'is_visible' => true,
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
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/library');

        $response
            ->assertOk()
            ->assertJsonMissing([
                'id' => $item->id,
                'title' => 'Expired Subscription Item',
            ]);
    }

    public function test_hidden_item_is_not_in_library_even_with_direct_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'title' => 'Hidden Item',
            'access_type' => 'paid',
            'is_visible' => false,
        ]);

        Access::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/library');

        $response
            ->assertOk()
            ->assertJsonMissing([
                'id' => $item->id,
                'title' => 'Hidden Item',
            ]);
    }
}
