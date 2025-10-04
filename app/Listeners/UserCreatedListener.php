<?php
namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Log;
use App\Events\UserCreated;
use App\User;

class UserCreatedListener implements ShouldQueue {

    public function handle(UserCreated $event) {
        Log::info('User was created: '.($event->user->url ?: $event->user->id));
        $event->user->fetchProfileInfo();
    }

}
