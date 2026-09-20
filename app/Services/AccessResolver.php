<?php

namespace App\Services;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;

class AccessResolver
{
    public function canAccess(User $user, Item $item): bool
    {
        $hasDirectAccess = Access::query()
            ->where('user_id', $user->id)
            ->where('item_id', $item->id)
            ->exists();

        if ($hasDirectAccess) {
            return true;
        }

        if ($item->access_type === 'paid') {
            return $user->subscriptions()
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>', now())
                ->exists();
        }

        return false;
    }
}
