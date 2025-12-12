<?php

namespace App\Services;

use App\Contracts\AssetRepositoryInterface;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Enum\OrderStatus;
use App\Jobs\MatchOrderJob;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private UserRepositoryInterface $userRepository,
        private AssetRepositoryInterface $assetRepository
    ) {
    }

    public function getAllOpenOrdersBySymbol(string $symbol)
    {
        if (!$symbol) {
            throw new \Exception('Invalid symbol');
        }
        return $this->orderRepository->getAllOpenOrdersBySymbol($symbol);
    }

    /**
     * Place a new order after validating user balance/assets.
     */
    public function placeOrder(int $userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            $user = $this->userRepository->getUserForUpdate($userId);

            if ($data['side'] === 'buy') {
                $cost = $data['price'] * $data['amount'];
                if ($user->balance < $cost) {
                    throw new \Exception('Insufficient balance');
                }
                $user->decrement('balance', $cost);
            } else {
                $asset = $this->assetRepository->findByUserSymbolForUpdate($user->id, $data['symbol']);

                if (!$asset || $asset->amount < $data['amount']) {
                    throw new \Exception("Insufficient assets");
                }
                $asset->decrement('amount', $data['amount']);
                $asset->increment('locked_amount', $data['amount']);
            }

            $order = $this->userRepository->createUserOrder($user, array_merge($data, [
                'status' => OrderStatus::OPEN,
            ]));

            MatchOrderJob::dispatch($order);

            return $order;
        });
    }

    /**
     * Cancel an open order and release funds/assets.
     */
    public function cancelOrder(int $orderId, int $userId)
    {
        return DB::transaction(function() use ($orderId, $userId) {
            $order = $this->orderRepository->findForUpdate($orderId);
            if (!$order || $order->user_id !== $userId) {
                throw new \Exception('Order not found or not authorized');
            }
            if ($order->status !== OrderStatus::OPEN) {
                throw new \Exception('Order not open');
            }

            if ($order->side === 'buy') {
                $user = $this->userRepository->getUserForUpdate($userId);
                $refund = $order->price * $order->amount;
                $user->increment('balance', $refund);
            } else {
                $asset = $this->assetRepository->findByUserSymbolForUpdate($userId, $order->symbol);
                $asset->decrement('locked_amount', $order->amount);
                $asset->increment('amount', $order->amount);
            }

            $this->orderRepository->update($order, ['status' => OrderStatus::CANCELLED]);

            return $order;
        });
    }
}