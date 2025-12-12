<?php

namespace App\Services;

use App\Contracts\AssetRepositoryInterface;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Enum\OrderStatus;
use Illuminate\Support\Facades\DB;

class MatchService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private UserRepositoryInterface $userRepository,
        private AssetRepositoryInterface $assetRepository
    ) {
    }
    
    /**
     * Match an open order with a counter order.
     */
    public function match(int $orderId)
    {
        return DB::transaction(function () use ($orderId) {
            $order = $this->orderRepository->findForUpdate($orderId);
            if (!$order || $order->status !== OrderStatus::OPEN) return;

            $counter = $this->orderRepository->findOpenCounter($order->symbol, $order->side, $order->price);
            if (!$counter) return;

            $buyOrder  = $order->side === 'buy' ? $order : $counter;
            $sellOrder = $order->side === 'sell' ? $order : $counter;

            // Commission Calculation (1.5%)
            $volume = $buyOrder->amount * $sellOrder->price;
            $fee = $volume * 0.015;
            $sellerNet = $volume - $fee;

            // Update order status
            $buyOrder->update(['status' => OrderStatus::FILLED]);
            $sellOrder->update(['status' => OrderStatus::FILLED]);

            // Buyer gets assets
            $buyerAsset = $this->assetRepository->firstOrCreate($buyOrder->user_id, $buyOrder->symbol);
            $buyerAsset->increment('amount', $buyOrder->amount);

            // Seller gets USD and cleared locked amount
            $seller = $this->userRepository->getUserForUpdate($sellOrder->user_id);
            $seller->increment('balance', $sellerNet);

            $sellerAsset = $this->assetRepository->findByUserSymbolForUpdate($sellOrder->user_id, $sellOrder->symbol);
            $sellerAsset->decrement('locked_amount', $sellOrder->amount);
        });
    }
}