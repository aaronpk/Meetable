<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class EventRevision extends Event
{

	public function num_changed_fields(Event $previous) {
		return count($this->changed_fields($previous));
	}

	public function changed_fields(Event $previous) {
		$editable = self::$EDITABLE_PROPERTIES;
		$editable[] = 'tags';

		$changes = [];
		foreach($editable as $p) {
			if($this->{$p} != $previous->{$p})
				$changes[] = $p;
		}
		return $changes;
	}

	public function revision_diff_permalink() {
        return route('view-revision-diff', [$this->event_id, $this->id]);
	}

	public static function createFromEvent(Event $event) {
		$revision = new EventRevision;

		$revision->event_id = $event->id;

        $revision->sort_date = $event->sort_date;
        $revision->slug = $event->slug;
        $revision->key = $event->key;
        $revision->last_modified_by = $event->last_modified_by;
        $revision->created_by = $event->created_by;
        $revision->zoom_meeting_id = $event->zoom_meeting_id ?: '';

        foreach(Event::$EDITABLE_PROPERTIES as $p) {
            $revision->{$p} = $event->{$p};
        }

        $tags = [];
        foreach($event->tags as $tag) {
        	$tags[] = $tag->tag;
        }
        $revision->tags = json_encode($tags);

        return $revision;
	}

}
