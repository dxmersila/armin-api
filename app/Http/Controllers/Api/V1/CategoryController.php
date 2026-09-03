<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryIndexRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ItemResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(CategoryIndexRequest $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(12);

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): AnonymousResourceCollection
    {
        $items = $category->items()
            ->where('is_visible', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->with([
                'categories',
                'tags',
                'media.file',
            ])
            ->paginate(12);

        return ItemResource::collection($items);
    }
}
