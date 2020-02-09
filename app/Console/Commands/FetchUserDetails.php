<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Log;
use App\User;
use p3k\XRay;

class FetchUserDetails extends Command {

  protected $signature = 'user:fetch_details {url?}';
  protected $description = 'Fetch user details for a user';

  public function handle() {
    $this->info('Fetching user details');

    if($this->argument('url'))
        $users = User::where('url', $this->argument('url'))->get();
    else
        $users = User::all();

    foreach($users as $user) {
      $this->info('Fetching user info for '.$user->url);

      $xray = new XRay();
      $data = $xray->parse($user->url);

      if(isset($data['data']['type']) && $data['data']['type'] == 'card') {
        $user->name = $data['data']['name'];
        $user->photo = $user->downloadProfilePhoto($data['data']['photo']);
        $this->info('  Found user details: '.$user->name.' '.$user->photo);
      } else {
        $this->info('  No h-card found');
        $this->info(json_encode($data));
      }

      $user->save();
    }
  }

}
