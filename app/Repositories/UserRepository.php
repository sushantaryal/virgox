<?php
namespace App\Repositories;

use App\Models\User;
use App\Contracts\UserRepositoryInterface;
use App\Models\Order;

class UserRepository implements UserRepositoryInterface
{
    public function getUserForUpdate(int $id): ?User
    {
        return User::where('id', $id)->lockForUpdate()->first();
    }

    public function updateBalance(User $user, string $newBalance): User
    {
        $user->balance = $newBalance;
        $user->save();
        return $user;
    }

    public function createUserOrder(User $user, array $data): Order
    {
        return $user->orders()->create($data);
    }

    public function getUserAssets(User $user)
    {
        return $user->assets->map(function ($asset) {
            return [
                'symbol' => $asset->symbol,
                'amount' => $asset->amount,
                'locked_amount' => $asset->locked_amount,
            ];
        });
    }

    public function getUserOrders(User $user)
    {
        return $user->orders->map(function ($order) {
            return [
                'id' => $order->id,
                'symbol' => $order->symbol,
                'side' => $order->side,
                'price' => $order->price,
                'amount' => $order->amount,
                'status' => $order->status->label(),
            ];
        });
    }
}
