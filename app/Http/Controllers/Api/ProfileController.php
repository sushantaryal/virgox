<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(protected UserService $userService)
    {}

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $userData = $this->userService->getUserBalanceAndAssets($request->user());

        return response()->json([
            'balance' => $userData['balance'],
            'assets' => $userData['assets'],
        ]);
    }
}
