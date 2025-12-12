<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\MatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MatchOrderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order)
    {        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $matchService = app(MatchService::class);
        $matchService->match($this->order->id);
    }
}
