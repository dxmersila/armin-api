<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_media(): void
    {
        $item = Item::factory()->create();

        $response = $this->postJson("/api/v1/admin/items/{$item->id}/media");

        $response->assertStatus(401);
    }


    public function test_regular_user_cannot_upload_media(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $item = Item::factory()->create();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/v1/admin/items/{$item->id}/media");

        $response->assertStatus(403);
    }


    public function test_admin_can_upload_media(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create();

        $file = UploadedFile::fake()->create(
            'test.jpg',
            100,
            'image/jpeg'
        );

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/items/{$item->id}/media", [
                'file' => $file,
                'is_primary' => true,
                'sort_order' => 0,
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'is_primary',
                    'sort_order',
                    'file',
                ],
            ]);

        $this->assertDatabaseHas('files', [
            'name' => 'test.jpg',
        ]);

        $this->assertDatabaseHas('media', [
            'item_id' => $item->id,
            'is_primary' => true,
        ]);
    }
}
