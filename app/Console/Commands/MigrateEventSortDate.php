<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Log, Storage;
use App\Event;

class MigrateEventSortDate extends Command {

    protected $signature = 'migrate:event_sort_date';
    protected $description = 'Add sort date to all events';

    public function handle() {

        $events = Event::withTrashed()->get();
        foreach($events as $event) {
            $event->sort_date = $event->sort_date()->format('Y-m-d H:i:s');
            $event->save();
        }

    }

}
