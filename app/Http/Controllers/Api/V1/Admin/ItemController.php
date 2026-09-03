<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\AdminItemResource;
use App\Models\Media;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ItemController extends Controller
{
    private const RELATIONS = [
        'categories',
        'tags',
        'media.file',
    ];

    /**
     * Display a listing of all items for admin.
     */
    public function index(): AnonymousResourceCollection
    {
        $items = Item::with(self::RELATIONS)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return AdminItemResource::collection($items);
    }

    /**
     * Store a newly created resource.
     */
    public function store(StoreItemRequest $request): JsonResponse
    {
        $item = DB::transaction(function () use ($request) {

            $data = $request->validated();

            $data['slug'] = $data['slug'] ?? Str::slug($data['title']);

            $data['is_visible'] = $data['is_visible'] ?? false;
            $data['access_type'] = $data['access_type'] ?? 'free';

            $categories = $data['categories'] ?? [];
            $tags = $data['tags'] ?? [];

            unset($data['categories'], $data['tags']);

            $item = Item::create($data);

            $item->categories()->sync($categories);
            $item->tags()->sync($tags);

            return $item;
        });

        $item->load(self::RELATIONS);

        return (new AdminItemResource($item))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item): AdminItemResource
    {
        $item->load(self::RELATIONS);

        return new AdminItemResource($item);
    }

    /**
     * Update the specified resource.
     */
    public function update(
        UpdateItemRequest $request,
        Item $item
    ): JsonResponse {
        DB::transaction(function () use ($request, $item) {

            $data = $request->validated();

            $categories = $data['categories'] ?? null;
            $tags = $data['tags'] ?? null;

            unset($data['categories'], $data['tags']);

            $item->update($data);

            if ($categories !== null) {
                $item->categories()->sync($categories);
            }

            if ($tags !== null) {
                $item->tags()->sync($tags);
            }
        });

        $item->load(self::RELATIONS);

        return (new AdminItemResource($item))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Item $item): Response
    {
        $mediaFiles = $item->media()
            ->with('file')
            ->get()
            ->map(fn(Media $media) => [
                'file_id' => $media->file_id,
                'path' => $media->file->path,
            ]);

        DB::transaction(function () use ($item) {
            $item->categories()->detach();
            $item->tags()->detach();

            $item->delete();
        });

        foreach ($mediaFiles as $mediaFile) {
            $fileStillInUse = Media::query()
                ->where('file_id', $mediaFile['file_id'])
                ->exists();

            if ($fileStillInUse) {
                continue;
            }

            Storage::disk('public')->delete($mediaFile['path']);

            File::query()
                ->whereKey($mediaFile['file_id'])
                ->delete();
        }

        return response()->noContent();
    }
}
