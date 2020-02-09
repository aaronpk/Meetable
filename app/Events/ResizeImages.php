<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\ResponsePhoto;

class ResizeImages
{
    use Dispatchable, SerializesModels;

    public $photo;

    public function __construct(ResponsePhoto $photo)
    {
        $this->photo = $photo;
    }
}
