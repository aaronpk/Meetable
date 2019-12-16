<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Log;
use App\User;
use p3k\XRay;

class FetchUserDetails extends Command {

  protected $signature = 'user:fetch_details';
  protected $description = 'Fetch user details for all users';

  public function handle() {
    $this->info('Fetching user details');
    foreach(User::all() as $user) {
      $this->info('Fetching user info for '.$user->url);

      $xray = new XRay();
      $data = $xray->parse($user->url);

      if(isset($data['data']['type']) && $data['data']['type'] == 'card') {
        $user->name = $data['data']['name'];
        $user->photo = $this->download($user, $data['data']['photo']);
        $this->info('  Found user details: '.$user->name.' '.$user->photo);
      }

      $user->save();
    }
  }

    private function download($user, $url) {
        // Already downloaded
        if(\p3k\url\host_matches($url, env('APP_URL')))
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

        return env('APP_URL').'/public/'.$filename;
    }

}
