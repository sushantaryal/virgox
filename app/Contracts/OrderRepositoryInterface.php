<?php

namespace App\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function findForUpdate(int $id): ?Order;

    public function findOpenCounter(string $symbol, string $side, string $price): ?Order;

    public function update(Order $order, array $data): Order;

    public function listByUser(int $id);
}