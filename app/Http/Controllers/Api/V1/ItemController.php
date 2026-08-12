<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ItemController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Item::query()
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
        $item->load([
            'categories',
            'tags',
            'media.file',
        ]);

        return new ItemResource($item);
    }
}
