<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Response;

class WebmentionReceived
{
    use Dispatchable, SerializesModels;

    public $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }
}
