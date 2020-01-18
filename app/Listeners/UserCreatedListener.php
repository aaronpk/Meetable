<?php
namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Storage, Log;
use App\Events\UserCreated;
use App\User;
use p3k\XRay;

class UserCreatedListener implements ShouldQueue {

    public function handle(UserCreated $event) {
        Log::info('User was created: '.($event->user->url ?: $event->user->id));

        $user = $event->user;

        $xray = new XRay();
        $data = $xray->parse($user->url);

        if(isset($data['data']['type']) && $data['data']['type'] == 'card') {
            $user->name = $data['data']['name'];
            $user->photo = $user->downloadProfilePhoto($data['data']['photo']);
            Log::info('  Found user details: '.$user->name.' '.$user->photo);
            $user->save();
        }
    }

}
