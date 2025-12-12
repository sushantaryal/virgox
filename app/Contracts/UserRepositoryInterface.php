<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\User;

interface UserRepositoryInterface
{
    public function getUserForUpdate(int $id): ?User;

    public function updateBalance(User $user, string $newBalance): User;

    public function createUserOrder(User $user, array $data): Order;

    public function getUserAssets(User $user);
}