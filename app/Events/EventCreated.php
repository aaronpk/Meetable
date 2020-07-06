<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Event;

class EventCreated
{
    use Dispatchable, SerializesModels;

    public $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }
}
