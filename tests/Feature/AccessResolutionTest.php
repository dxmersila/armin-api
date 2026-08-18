<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;
use App\Services\AccessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_free_item(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'access_type' => 'free',
        ]);

        $resolver = app(AccessResolver::class);

        $this->assertTrue(
            $resolver->canAccess($user, $item)
        );
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

        $resolver = app(AccessResolver::class);

        $this->assertTrue(
            $resolver->canAccess($user, $item)
        );
    }

    public function test_authenticated_user_cannot_access_paid_item_without_access(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $resolver = app(AccessResolver::class);

        $this->assertFalse(
            $resolver->canAccess($user, $item)
        );
    }
}
