<?php

namespace App\Services;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;

class AccessResolver
{
    public function canAccess(User $user, Item $item): bool
    {
        if ($item->access_type === 'free') {
            return true;
        }

        return Access::where('user_id', $user->id)
            ->where('item_id', $item->id)
            ->exists();
    }
}
