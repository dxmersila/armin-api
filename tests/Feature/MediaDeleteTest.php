<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Item;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete_media(): void
    {
        $item = Item::factory()->create();

        $response = $this->deleteJson(
            "/api/v1/admin/items/{$item->id}/media/1"
        );

        $response->assertStatus(401);
    }


    public function test_regular_user_cannot_delete_media(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $item = Item::factory()->create();

        $file = File::create([
            'name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1000,
        ]);

        $media = Media::create([
            'item_id' => $item->id,
            'file_id' => $file->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/admin/items/{$item->id}/media/{$media->id}");

        $response->assertStatus(403);
    }


    public function test_admin_can_delete_media(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create();

        $file = File::create([
            'name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1000,
        ]);

        Storage::disk('public')->put(
            'media/test.jpg',
            'dummy content'
        );

        $media = Media::create([
            'item_id' => $item->id,
            'file_id' => $file->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson(
                "/api/v1/admin/items/{$item->id}/media/{$media->id}"
            );

        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Media deleted successfully',
            ]);

        $this->assertDatabaseMissing('media', [
            'id' => $media->id,
        ]);

        $this->assertDatabaseMissing('files', [
            'id' => $file->id,
        ]);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        $disk->assertMissing('media/test.jpg');
    }
}
