<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Log, Storage;
use App\Event;
use DateTime, DatePeriod;

class CreateRecurringEventInstances extends Command {

    protected $signature = 'recurring:schedule';
    protected $description = 'Create events from the scheduled recurring events';

    // Schedule this to run at least once a day

    public function handle() {

        $events = Event::where('is_template', 1)
          ->orderBy('sort_date', 'desc')
          ->get();

        foreach($events as $event) {
            $event->create_upcoming_recurrences();
        }

    }

}
