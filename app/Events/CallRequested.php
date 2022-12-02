<?php

namespace App\Events;

use App\Models\Person;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallRequested implements ShouldBroadcastNow
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Person $from;
    public Person $to;
    public string $offer;

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'call.requested';
    }

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Person $from, Person $to, $offer)
    {
        $this->from = $from;
        $this->to = $to;
        $this->offer = $offer;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|PrivateChannel|array
     */
    public function broadcastOn(): Channel|PrivateChannel|array
    {
        return new Channel('call.' . $this->to->username);
    }
}
