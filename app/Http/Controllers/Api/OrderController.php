<?php

namespace App\Http\Controllers\Api;

use App\Enum\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Asset;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Returns all open orders for orderbook
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->placeOrder($request->user()->id, $request->validated());

        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function cancel(Request $request, string $id)
    {
        return $this->orderService->cancelOrder($id, $request->user()->id);
    }
}
