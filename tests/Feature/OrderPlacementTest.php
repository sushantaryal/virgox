<?php

namespace Tests\Feature;

use App\Enum\OrderStatus;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Exceptions;
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
}
