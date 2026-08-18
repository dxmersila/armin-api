<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $access = Access::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->assertTrue($access->user->is($user));
    }

    public function test_access_belongs_to_item(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $access = Access::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->assertTrue($access->item->is($item));
    }

    public function test_user_has_many_accesses(): void
    {
        $user = User::factory()->create();
        $itemOne = Item::factory()->create();
        $itemTwo = Item::factory()->create();

        Access::create([
            'user_id' => $user->id,
            'item_id' => $itemOne->id,
        ]);

        Access::create([
            'user_id' => $user->id,
            'item_id' => $itemTwo->id,
        ]);

        $this->assertCount(2, $user->accesses);
    }

    public function test_item_has_many_accesses(): void
    {
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();
        $item = Item::factory()->create();

        Access::create([
            'user_id' => $userOne->id,
            'item_id' => $item->id,
        ]);

        Access::create([
            'user_id' => $userTwo->id,
            'item_id' => $item->id,
        ]);

        $this->assertCount(2, $item->accesses);
    }

    public function test_user_cannot_have_duplicate_access_to_same_item(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        Access::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Access::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }
}
