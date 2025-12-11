<?php

namespace App\Http\Controllers\Api;

use App\Enum\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'side' => 'required|in:buy,sell',
            'price' => 'required|numeric|gt:0',
            'amount' => 'required|numeric|gt:0',
        ]);

        $authUser = $request->user();

        $order = DB::transaction(function () use ($validated, $authUser) {
            $user = User::where('id', $authUser->id)->lockForUpdate()->first();

            if ($validated['side'] === 'buy') {
                $cost = $validated['price'] * $validated['amount'];
                if ($user->balance < $cost) {
                    throw new \Exception('Insufficient balance');
                }
                $user->decrement('balance', $cost);
            } else {
                $asset = Asset::where('user_id', $user->id)
                    ->where('symbol', $validated['symbol'])
                    ->lockForUpdate()
                    ->first();

                if (!$asset || $asset->amount < $validated['amount']) {
                    throw new \Exception("Insufficient assets");
                }
                $asset->decrement('amount', $validated['amount']);
                $asset->increment('locked_amount', $validated['amount']);
            }

            $order = $user->orders()->create(array_merge($validated, [
                'status' => OrderStatus::OPEN,
            ]));

            return $order;
        });

        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function cancel(string $id)
    {
        // Cancels an open order and releases locked USD or assets
    }
}
