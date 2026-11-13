<?php

namespace App\Events;

use App\Models\Wallet\SweepTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TreasurySweepUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SweepTransaction $sweep) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.treasury'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sweep.updated';
    }
}

