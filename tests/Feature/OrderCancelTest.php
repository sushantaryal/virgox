<?php

namespace Tests\Feature;

use App\Enum\OrderStatus;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderCancelTest extends TestCase
{
    use RefreshDatabase;

    protected $buyer;
    protected $seller;

    public function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()
            ->create(['balance' => 100000]);

        $this->seller = User::factory()
            ->has(
                Asset::factory()
                    ->state(['symbol' => 'BTC', 'amount' => 1, 'locked_amount' => 0])
            )
            ->create();
    }

    public function test_buyer_user_can_cancel_open_order_releases_locked_fund(): void
    {
        $this->actingAs($this->buyer, 'sanctum');

        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000,
            'amount' => 0.50,
        ]);

        $response->assertOk(200);

        $order = $this->buyer->orders()->first();

        $cancelResponse = $this->postJson("/api/orders/{$order->id}/cancel");

        $cancelResponse->assertOk(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED,
        ]);

        $this->assertEquals(100000.00, $this->buyer->refresh()->balance);
    }

    public function test_seller_user_can_cancel_open_order_releases_locked_asset(): void
    {
        $this->actingAs($this->seller, 'sanctum');

        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => 60000,
            'amount' => 0.10,
        ]);

        $response->assertOk(200);

        $order = $this->seller->orders()->first();

        $cancelResponse = $this->postJson("/api/orders/{$order->id}/cancel");

        $cancelResponse->assertOk(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED,
        ]);

        $this->assertEquals(1, $this->seller->assets()->where('symbol', 'BTC')->first()->amount);
        $this->assertEquals(0, $this->seller->assets()->where('symbol', 'BTC')->first()->locked_amount);
    }
}
