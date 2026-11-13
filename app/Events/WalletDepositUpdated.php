<?php

namespace App\Events;

use App\Models\Wallet\Deposit;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletDepositUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Deposit $deposit) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("wallet.user.{$this->deposit->user_id}"),
            new PrivateChannel('admin.treasury'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'deposit.updated';
    }
}

