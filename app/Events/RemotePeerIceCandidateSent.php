<?php

namespace App\Events;

use App\Models\Person;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemotePeerIceCandidateSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Person $from;
    public Person $to;
    public string $candidate;

    /**
     *  Create a new event instance.
     *
     * @param Person $from
     * @param Person $to
     * @param string $candidate
     *
     * @return void
     */

    public function __construct(Person $from, Person $to, string $candidate)
    {
        $this->from = $from;
        $this->to = $to;
        $this->candidate = $candidate;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'call.candidate.sent';
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
