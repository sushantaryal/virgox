<?php

namespace Tests\Feature;

use App\Enum\OrderStatus;
use App\Jobs\MatchOrderJob;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderPlacementTest extends TestCase
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

    public function test_user_can_place_buy_order(): void
    {
        $this->actingAs($this->buyer, 'sanctum');

        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000,
            'amount' => 0.50,
        ]);

        $response->assertOk(200);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->buyer->id,
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000.00,
            'amount' => 0.50,
            'status' => OrderStatus::OPEN,
        ]);

        $this->assertEquals(75000.00, $this->buyer->refresh()->balance);
    }

    public function test_user_cannot_buy_more_than_their_balance(): void
    {
        Exceptions::fake();

        $this->actingAs($this->buyer, 'sanctum');

        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 120000,
            'amount' => 1,
        ]);

        $response->assertStatus(500);

        Exceptions::assertReported(function (\Exception $e) {
            return $e->getMessage() === 'Insufficient balance';
        });
    }

    public function test_user_can_place_sell_order(): void
    {
        $this->actingAs($this->seller, 'sanctum');

        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => 60000,
            'amount' => 0.10,
        ]);

        $response->assertOk(200);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->seller->id,
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => 60000.00,
            'amount' => 0.10,
            'status' => OrderStatus::OPEN,
        ]);

        $this->assertEquals(0.90, $this->seller->assets()->where('symbol', 'BTC')->first()->amount);
        $this->assertEquals(0.10, $this->seller->assets()->where('symbol', 'BTC')->first()->locked_amount);
    }

    public function test_user_cannot_sell_more_than_their_assets(): void
    {
        Exceptions::fake();

        $this->actingAs($this->seller, 'sanctum');

        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => 60000,
            'amount' => 1.10,
        ]);

        $response->assertStatus(500);

        Exceptions::assertReported(function (\Exception $e) {
            return $e->getMessage() === 'Insufficient assets';
        });
    }

    public function test_buy_and_sell_orders_match(): void
    {
        $this->actingAs($this->seller, 'sanctum');

        $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => 50000,
            'amount' => 0.50,
        ]);

        $this->actingAs($this->buyer, 'sanctum');

        $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000,
            'amount' => 0.50,
        ]);

        (new MatchOrderJob($this->buyer->orders()->first()))->handle();

        $this->assertEquals(1, $this->buyer->assets()->count());

        $this->assertEquals(0.50, $this->buyer->assets()->where('symbol', 'BTC')->first()->amount);
        $this->assertEquals(0.50, $this->seller->assets()->where('symbol', 'BTC')->first()->amount);
        $this->assertEquals(0.00, $this->seller->assets()->where('symbol', 'BTC')->first()->locked_amount);

        $volume = 0.50 * 50000;
        $fee = $volume * 0.015;
        $sellerNet = $volume - $fee;
        $sellerBalance = $this->seller->balance + $sellerNet;
        $this->assertEquals($sellerBalance, $this->seller->refresh()->balance);

        $this->assertEquals(2, Order::where('status', OrderStatus::FILLED)->count());
    }
}
