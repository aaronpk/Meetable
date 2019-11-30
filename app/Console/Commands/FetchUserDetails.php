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
        $user->photo = $data['data']['photo'];
        $this->info('  Found user details: '.$user->name.' '.$user->photo);
      }

      $user->save();

    }
  }

}
