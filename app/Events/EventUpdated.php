<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Event, App\EventRevision;

class EventUpdated
{
    use Dispatchable, SerializesModels;

    public $event;
    public $revision;

    public function __construct(Event $event, EventRevision $revision)
    {
        $this->event = $event;
        $this->revision = $revision;
    }
}
