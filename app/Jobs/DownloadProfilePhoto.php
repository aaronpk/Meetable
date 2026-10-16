<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\User;

/**
 * Downloads a user's profile photo from the login provider and stores it locally.
 *
 * Logins queue this instead of downloading inline so the callback never waits on the
 * avatar host. A photo that can't be downloaded leaves the user's current photo alone.
 */
class DownloadProfilePhoto implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $url;

    public function __construct(User $user, $url) {
        $this->user = $user;
        $this->url = $url;
    }

    public function handle() {
        $photo = $this->user->downloadProfilePhoto($this->url);

        if($photo) {
            $this->user->photo = $photo;
            $this->user->save();
        }
    }

}
