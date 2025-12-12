<?php
namespace App\Repositories;

use App\Models\Order;
use App\Contracts\OrderRepositoryInterface;
use App\Enum\OrderStatus;

class OrderRepository implements OrderRepositoryInterface
{
    public function findForUpdate(int $id): ?Order
    {
        return Order::where('id', $id)->lockForUpdate()->first();
    }

    public function findOpenCounter(string $symbol, string $side, string $price): ?Order
    {
        $opposite = $side === 'buy' ? 'sell' : 'buy';

        return Order::where('symbol', $symbol)
            ->where('side', $opposite)
            ->where('status', OrderStatus::OPEN)
            ->when($side === 'buy', function ($query) use ($price) {
                $query->where('price', '<=', $price);
            }, function ($query) use ($price) {
                $query->where('price', '>=', $price);
            })
            ->oldest()
            ->lockForUpdate()
            ->first();
    }

    public function update(Order $order, array $data): Order
    {
        $order->fill($data);
        $order->save();
        return $order;
    }

    public function listByUser(int $id)
    {
        return Order::where('user_id', $id)->orderBy('created_at','desc')->get();
    }
}
