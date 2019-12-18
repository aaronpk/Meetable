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
            $user->photo = $this->download($user, $data['data']['photo']);
            Log::info('  Found user details: '.$user->name.' '.$user->photo);
            $user->save();
        }
    }

    private function download($user, $url) {
        // Already downloaded
        if(!parse_url($url, PHP_URL_HOST))
            return $url;

        $filename = 'users/'.$user->id.'-'.md5($url);
        $full_filename = __DIR__.'/../../storage/app/public/'.$filename;

        $dir = dirname($full_filename);
        if(!file_exists($dir))
            mkdir($dir, 0755, true);

        $fp = fopen($full_filename, 'w+');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        return 'public/'.$filename;
    }

}
