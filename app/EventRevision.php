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

}
