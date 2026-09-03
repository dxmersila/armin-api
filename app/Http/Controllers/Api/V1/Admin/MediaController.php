<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\File;
use App\Models\Item;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function store(StoreMediaRequest $request, Item $item): JsonResponse
    {
        $uploadedFile = $request->file('file');

        $fileName = Str::uuid() . '.' . $uploadedFile->getClientOriginalExtension();

        $path = $uploadedFile->storeAs(
            'media',
            $fileName,
            'public'
        );

        $file = File::create([
            'name' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
        ]);

        $isPrimary = $request->boolean('is_primary');

        if ($isPrimary) {
            $item->media()
                ->where('is_primary', true)
                ->update([
                    'is_primary' => false,
                ]);
        }

        $media = Media::create([
            'item_id' => $item->id,
            'file_id' => $file->id,
            'is_primary' => $isPrimary,
            'sort_order' => $request->integer('sort_order'),
        ]);

        $media->load('file');

        return (new MediaResource($media))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Item $item, Media $media): JsonResponse
    {
        abort_unless($media->item_id === $item->id, 404);

        $file = $media->file;
        $fileId = $file->id;
        $filePath = $file->path;

        DB::transaction(function () use ($media, $fileId) {
            $media->delete();

            $fileStillInUse = Media::query()
                ->where('file_id', $fileId)
                ->exists();

            if (! $fileStillInUse) {
                File::query()
                    ->whereKey($fileId)
                    ->delete();
            }
        });

        if (! Media::query()->where('file_id', $fileId)->exists()) {
            Storage::disk('public')->delete($filePath);
        }

        return response()->json([
            'message' => 'Media deleted successfully',
        ]);
    }
}
