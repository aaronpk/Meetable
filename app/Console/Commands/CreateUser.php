<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Log;
use App\User;
use Illuminate\Support\Str;

class CreateUser extends Command {

    protected $signature = 'user:create {url}';
    protected $description = 'Create a user account and API token';

    public function handle() {
        $url = $this->argument('url');

        $user = new User();
        $user->url = $url;
        $user->api_token = Str::random(80);
        $user->save();

        $this->info('API Token: '.$user->api_token);
    }

}
