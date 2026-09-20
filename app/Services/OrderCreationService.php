<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

class OrderCreationService
{
    public function __construct(
        private readonly OrderPricingService $pricingService,
    ) {}

    /**
     * Create an order from the given item IDs.
     *
     * @param array<int> $itemIds
     */
    public function create(User $user, array $itemIds): Order
    {
        if ($itemIds === []) {
            throw new InvalidArgumentException(
                'An order must contain at least one item.'
            );
        }

        return DB::transaction(function () use ($user, $itemIds) {
            $itemIds = array_values(array_unique($itemIds));

            $items = Item::query()
                ->whereIn('id', $itemIds)
                ->get();

            if ($items->count() !== count($itemIds)) {
                throw new InvalidArgumentException(
                    'One or more items do not exist.'
                );
            }

            foreach ($items as $item) {
                if ($item->access_type !== 'paid' || ! $item->is_visible) {
                    throw ValidationException::withMessages([
                        'item_ids' => "Item {$item->id} is not available for purchase.",
                    ]);
                }

                if ($user->accesses()
                    ->where('item_id', $item->id)
                    ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'item_ids' => "Item {$item->id} has already been purchased.",
                    ]);
                }
            }

            $requestedItemIds = collect($itemIds)
                ->sort()
                ->values()
                ->all();

            $existingOrders = Order::query()
                ->where('user_id', $user->id)
                ->where('type', OrderType::ITEM)
                ->whereIn('status', [
                    OrderStatus::PENDING,
                    OrderStatus::CANCELLED,
                ])
                ->with('orderItems')
                ->orderByDesc('id')
                ->get();

            foreach ($existingOrders as $existingOrder) {
                $existingItemIds = $existingOrder->orderItems
                    ->pluck('item_id')
                    ->sort()
                    ->values()
                    ->all();

                if ($existingItemIds !== $requestedItemIds) {
                    continue;
                }

                $existingOrder->update([
                    'status' => OrderStatus::PENDING,
                ]);

                foreach ($items as $item) {
                    $existingOrder->orderItems()
                        ->where('item_id', $item->id)
                        ->update([
                            'price' => $item->price,
                        ]);
                }

                $this->pricingService->update($existingOrder);

                return $existingOrder->load('orderItems');
            }

            $order = Order::create([
                'user_id' => $user->id,
                'type' => OrderType::ITEM,
                'status' => OrderStatus::PENDING,
                'total_price' => 0,
            ]);

            foreach ($items as $item) {
                $order->orderItems()->create([
                    'item_id' => $item->id,
                    'price' => $item->price,
                ]);
            }

            $this->pricingService->update($order);

            return $order->load('orderItems');
        });
    }
}
