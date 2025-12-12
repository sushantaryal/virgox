<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserOrderController extends Controller
{
    public function __construct(protected UserService $userService)
    {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return response()->json([
            'orders' => $this->userService->getUserOrders($request->user())
        ]);
    }
}
