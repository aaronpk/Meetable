<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Log, Storage;
use App\User, App\Event;
use Image;

class MigrateUserImages extends Command {

    protected $signature = 'migrate:user_images';
    protected $description = 'Migrate user avatars to the new storage location';

    public function handle() {

        $users = User::all();
        foreach($users as $user) {

            if($user->photo) {
                $this->info('Processing '.$user->photo);
                $user->photo = str_replace('public/', '/storage/', $user->photo);
                $user->save();
            }

        }

    }

}
