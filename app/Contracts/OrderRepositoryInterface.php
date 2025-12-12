<?php

namespace App\Contracts;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function findForUpdate(int $id): ?Order;

    public function getAllOpenOrdersBySymbol(string $symbol): Collection;

    public function findOpenCounter(string $symbol, string $side, string $price): ?Order;

    public function update(Order $order, array $data): Order;

    public function listByUser(int $id);
}