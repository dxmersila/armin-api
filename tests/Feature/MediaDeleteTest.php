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
            ->deleteJson(
                "/api/v1/admin/items/{$item->id}/media/{$media->id}"
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
        ]);

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
        ]);
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

    public function test_admin_cannot_delete_media_from_wrong_item(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create();

        $anotherItem = Item::factory()->create();

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
            'item_id' => $anotherItem->id,
            'file_id' => $file->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson(
                "/api/v1/admin/items/{$item->id}/media/{$media->id}"
            );

        $response->assertStatus(404);

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'item_id' => $anotherItem->id,
        ]);

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
        ]);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        $disk->assertExists('media/test.jpg');
    }

    public function test_shared_file_is_not_deleted_when_one_media_is_removed(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $itemOne = Item::factory()->create();
        $itemTwo = Item::factory()->create();

        $file = File::create([
            'name' => 'shared.jpg',
            'path' => 'media/shared.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1000,
        ]);

        Storage::disk('public')->put(
            'media/shared.jpg',
            'dummy content'
        );

        $mediaOne = Media::create([
            'item_id' => $itemOne->id,
            'file_id' => $file->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $mediaTwo = Media::create([
            'item_id' => $itemTwo->id,
            'file_id' => $file->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson(
                "/api/v1/admin/items/{$itemOne->id}/media/{$mediaOne->id}"
            );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('media', [
            'id' => $mediaOne->id,
        ]);

        $this->assertDatabaseHas('media', [
            'id' => $mediaTwo->id,
            'item_id' => $itemTwo->id,
            'file_id' => $file->id,
        ]);

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
        ]);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        $disk->assertExists('media/shared.jpg');
    }
}
