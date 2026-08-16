<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
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
            ->latest()
            ->paginate(15);

        return ItemResource::collection($items);
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

        return (new ItemResource($item))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item): ItemResource
    {
        $item->load(self::RELATIONS);

        return new ItemResource($item);
    }

    /**
     * Update the specified resource.
     */
    public function update(UpdateItemRequest $request, Item $item): JsonResponse
    {
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

        return (new ItemResource($item))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Item $item): Response
    {
        DB::transaction(function () use ($item) {
            $item->categories()->detach();
            $item->tags()->detach();

            $item->delete();
        });

        return response()->noContent();
    }
}
