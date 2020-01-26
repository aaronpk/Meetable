<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Log, Storage;
use App\User, App\Event;

class Backup extends Command {

    protected $signature = 'backup:all';
    protected $description = 'Back up all events into a folder on disk';

    public function handle() {

        $existingFiles = Storage::allFiles('events');
        print_r($existingFiles);

        $events = Event::all();
        foreach($events as $event) {

            $file = 'events'.$event->permalink().'.json';

            // Delete old versions of this event that may have been saved under different names
            foreach($existingFiles as $f) {
                if(preg_match('/-'.$event->key.'\.json/', $f)) {
                    if($f != $file) {
                        $this->info('Deleting old file: '.$f);
                        Storage::delete($f);
                    }
                }
            }

            // Load related models
            $event->load('tags', 'createdBy');
            $event->createdBy->makeHidden(['created_at', 'updated_at', 'is_admin']);

            $this->info('Backing up event to '.$file);
            Storage::put($file, $event->toJson(JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES));

        }

        $users = User::all();
        foreach($users as $user) {

            $file = 'users/'.$user->id.'.json';
            $this->info('Backing up user '.$user->url.' to '.$file);
            Storage::put($file, $user->toJson(JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES));

        }

    }

}
