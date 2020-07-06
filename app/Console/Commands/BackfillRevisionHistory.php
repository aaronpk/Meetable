<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Log, Storage;
use App\Event, App\EventRevision;

class MigrateEventSortDate extends Command {

    protected $signature = 'migrate:revision_history';
    protected $description = 'Add a new revision history entry for the current state of all events';

    public function handle() {

        $events = Event::withTrashed()->get();
        foreach($events as $event) {
	        $revision = new EventRevision;
	        foreach(Event::$EDITABLE_PROPERTIES as $p) {
	            $revision->{$p} = $event->{$p};
	        }
	        $revision->sort_date = $event->sort_date;
	        $revision->key = $event->key;
	        $revision->slug = $event->slug;
	        $revision->last_modified_by = $event->last_modified_by;
	        $revision->created_by = $event->created_by;
	        $tags_string = [];
	        foreach($event->tags as $tag)
	        	$tags_string[] = $tag->tag;
	        $revision->tags = json_encode($tags_string);
	        $revision->save();
        }

    }

}
