<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LibraryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $query = Item::query()
            ->where('is_visible', true)
            ->whereHas('accesses', function ($accessQuery) use ($user) {
                $accessQuery->where('user_id', $user->id);
            })
            ->with([
                'categories',
                'tags',
                'media.file',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return ItemResource::collection($query->paginate(12));
    }
}
