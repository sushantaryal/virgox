<?php

namespace App\Services;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Get user assets along with current balance.
     */
    public function getUserBalanceAndAssets(User $user)
    {
        return [
            'balance' => $user->balance,
            'assets' => $this->userRepository->getUserAssets($user)
        ];
    }

    /**
     * Get user orders.
     */
    public function getUserOrders(User $user)
    {
        return $this->userRepository->getUserOrders($user);
    }
}