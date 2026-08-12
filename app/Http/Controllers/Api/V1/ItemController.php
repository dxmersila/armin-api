<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ItemController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Item::query()
            ->where('is_visible', true)
            ->with([
                'categories',
                'tags',
                'media.file',
            ]);

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($categoryQuery) use ($request) {
                $categoryQuery->where('slug', $request->category);
            });
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($tagQuery) use ($request) {
                $tagQuery->where('slug', $request->tag);
            });
        }

        $items = $query->paginate(15);

        return ItemResource::collection($items);
    }

    public function show(Item $item): ItemResource
    {
        abort_unless($item->is_visible, 404);

        $item->load([
            'categories',
            'tags',
            'media.file',
        ]);

        return new ItemResource($item);
    }
}
