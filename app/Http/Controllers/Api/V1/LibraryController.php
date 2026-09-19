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

        $hasActiveSubscription = $user->subscriptions()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->exists();

        $query = Item::query()
            ->where('is_visible', true)
            ->where(function ($query) use ($user, $hasActiveSubscription) {
                $query
                    ->where('access_type', 'free')
                    ->orWhereHas('accesses', function ($accessQuery) use ($user) {
                        $accessQuery->where('user_id', $user->id);
                    });

                if ($hasActiveSubscription) {
                    $query->orWhere('access_type', 'paid');
                }
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
