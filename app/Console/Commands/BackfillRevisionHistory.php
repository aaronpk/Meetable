<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Log, Storage;
use App\Event, App\EventRevision;

class BackfillRevisionHistory extends Command {

	protected $signature = 'migrate:revision_history';
	protected $description = 'Add a new revision history entry for the current state of all events that are missing revisions';

	public function handle() {

		$events = Event::withTrashed()->get();
		foreach($events as $event) {
			$revisions = EventRevision::where('event_id', $event->id)->count();
			
			if($revisions == 0) {
				$this->info("Adding revision for event ".$event->id);
				$revision = EventRevision::createFromEvent($event);
				$revision->save();
			}
		}

	}

}
