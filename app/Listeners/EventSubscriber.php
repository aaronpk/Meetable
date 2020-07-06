<?php
namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Log;
use App\Event, App\EventRevision, App\Setting;
use App\Events\EventCreated, App\Events\EventUpdated;
use GuzzleHttp\Client;

class EventSubscriber implements ShouldQueue
{

	public function handleEventCreated(EventCreated $event) {
		Log::info('Event created: '.$event->event->id);
		$summary = '[New Event] '
			. $event->event->createdBy->display_url() . ' created'
			. ' "' . $event->event->name . '"'
			. ' on ' . $event->event->date_summary_text()
			. ' ' . $event->event->absolute_shortlink();
		$this->sendTextNotification($summary);
	}

	public function handleEventUpdated(EventUpdated $event) {
		Log::info('Event updated: '.$event->event->id.' revision '.$event->revision->id);

        $previous = EventRevision::where('event_id', $event->revision->event_id)
          ->where('id', '!=', $event->revision->id)
          ->where('created_at', '<', $event->revision->created_at)
          ->orderBy('created_at', 'desc')
          ->first();

        if(!$previous) {
        	Log::error('Could not find previous revision of event '.$event->event->id);
        	return;
        }

		$summary = '[Event Updated] '
			. $event->revision->createdBy->display_url() . ' updated'
			. ' "' . $event->event->name . '"'
			. ' on ' . $event->event->date_summary_text()
			. ' changed ' . implode(', ', $event->revision->changed_fields($previous))
			. ($event->revision->edit_summary ? ' "'.$event->revision->edit_summary.'"' : '')
			. ' ' . $event->revision->revision_diff_permalink();
		$this->sendTextNotification($summary);
	}

	private function sendTextNotification($text) {
		if(Setting::value('notification_endpoint')) {
			Log::info($text);
			$client = new Client();
			$response = $client->request('POST', Setting::value('notification_endpoint'), [
				'form_params' => [
					'h' => 'entry', // make it a micropub request
					'content' => $text,
				],
				'headers' => [
					'Authorization' => 'Bearer '.Setting::value('notification_token'),
				]
			]);
			Log::info('Notification response: '.$response->getStatusCode().' '.$response->getBody());
		}
	}

	public function subscribe($events) {
		$events->listen(
			'App\Events\EventCreated',
			'App\Listeners\EventSubscriber@handleEventCreated'
		);

		$events->listen(
			'App\Events\EventUpdated',
			'App\Listeners\EventSubscriber@handleEventUpdated'
		);
	}


}