<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ItemController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $items = Item::query()
            ->with([
                'categories',
                'tags',
                'media.file',
            ])
            ->paginate(15);

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
