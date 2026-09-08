<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class EventRevision extends Event
{

	/**
	 * Responses, photos and sub-events belong to the event this is a snapshot of,
	 * not to the snapshot itself, so those relations resolve through event_id.
	 */
	public function eventKeyName() {
		return 'event_id';
	}

	/**
	 * A revision keeps its tags as a JSON snapshot rather than as related rows,
	 * so this reads them back in the same shape the event's own accessor returns.
	 */
	public function getTagListAttribute() {
		return json_decode($this->tags, true) ?: [];
	}

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
            // An event that was just inserted hasn't picked up the values the
            // database filled in for columns it didn't set, so leave those to the
            // revision table's own defaults rather than writing an explicit null
            // into a column that doesn't allow one.
            if($event->{$p} !== null) {
                $revision->{$p} = $event->{$p};
            }
        }

        $tags = [];
        foreach($event->tags as $tag) {
        	$tags[] = $tag->tag;
        }
        $revision->tags = json_encode($tags);

        return $revision;
	}

}
