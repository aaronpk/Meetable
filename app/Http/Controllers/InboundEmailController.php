<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\Event, App\EventRevision, App\User, App\InboundEmail;
use App\Events\EventCreated, App\Events\EventUpdated;
use DateTime, DateTimeZone;
use DB, Log;
use ICal\ICal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Header\HeaderConsts;

class InboundEmailController extends BaseController
{

    public function parse_from_sendgrid(Request $request) {

        $raw_data = request('email');

        $message = Message::from($raw_data, false);

        $rawHeaderFrom = $message->getHeaderValue(HeaderConsts::FROM);
        Log::info('Parsing email from '.$rawHeaderFrom);

        $ics = false;
        $ics_src_type = false;

        $attachments = $message->getAllAttachmentParts();
        foreach($attachments as $attachment) {
            if(!$ics) {
                if($attachment->getContentType() == 'text/calendar') {
                    $ics = $attachment->getContent();
                    $ics_src_type = $attachment->getContentType();
                }
                if($attachment->getContentType() == 'application/ics') {
                    $ics = $attachment->getContent();
                    $ics_src_type = $attachment->getContentType();
                }
            }
        }

        // Look up sender to make sure they are an allowed sender
        $user = User::find_from_email($rawHeaderFrom);

        if(!$user) {
            Log::info('Received email from unknown user: '.$rawHeaderFrom);
            $this->log_inbound_email('invalid_user', $raw_data, $ics, null, null);
            return response()->json(['result' => 'invalid_user']);
        }

        if(!$ics) {
            Log::error('No ics file in email from '.$rawHeaderFrom);
            $this->log_inbound_email('no_ics', $raw_data, null, $user, null);
            return response()->json(['result' => 'no_ics']);
        }

        try {
            $ical = new ICal(false, [
                'skipRecurrence' => true
            ]);
            $ical->initString($ics);
        } catch(\Exception $e) {
            Log::error('Error parsing ics file from '.$rawHeaderFrom);
            $this->log_inbound_email('error_parsing_ics', $raw_data, null, $user, null);
            return response()->json(['result' => 'error_parsing_ics']);
        }

        if(!$ical->hasEvents()) {
            Log::error('No events found in ics from '.$rawHeaderFrom);
            $this->log_inbound_email('no_events', $raw_data, $ics, $user, null);
            return response()->json(['result' => 'no_events']);
        }

        $data = $ical->events()[0];

        if(isset($ical->cal['VCALENDAR']['METHOD']) && $ical->cal['VCALENDAR']['METHOD'] == 'CANCEL') {
            $event = Event::where('ics_uid', $data->uid)->first();
            $this->log_inbound_email('deleted', $raw_data, $ics, $user, $event);
            if($event) {
                Log::info('Deleting cancelled event: '.$event->absolute_permalink());
                $event->delete();
            }
            return response()->json(['result' => 'deleted']);
        }

        Log::info($data->uid);
        Log::info($data->summary);
        Log::info($data->dtstart);
        Log::info($data->dtend);
        Log::info($data->description);

        // Check if we've already created an event for this UID
        $event = Event::where('ics_uid', $data->uid)->withTrashed()->first();
        if(!$event) {
            $event = new Event();
            $event->key = Str::random(12);
            $event->ics_uid = $data->uid;
            $event->created_by = $user->id;
            $event->last_modified_by = $user->id;
            $event->fields_from_ics = json_encode(['name','description','datetime','location']);
            $is_new = true;
        } else {
            $is_new = false;
            $event->deleted_at = null;
        }


        $ignored = [];


        // NAME

        if($event->field_is_from_ics_invite('name')) {
            // Don't override the name/slug if the event name was changed manually
            $event->name = html_entity_decode($data->summary);
            $event->slug = Event::slug_from_name($event->name);
        } else {
            $ignored[] = 'name';
        }



        // DESCRIPTION

        $description = $data->description;

        if(($endpos = strpos($description, '-::~:~::~:~:~:~:~')) !== null) {
            // Google hides the timezone in the URL of the event, attempt to grab it from there
            $googleblob = substr($description, $endpos);
            if(preg_match('/calendar\.google\.com.+ctz=([A-Za-z_\/%2]+)/', $googleblob, $match)) {
                $event->timezone = $tz = urldecode($match[1]);
            }
            $description = trim(substr($description, 0, $endpos));
        }

        if(preg_match('~(https?:\/\/[^\s]+)~', $description, $match)) {
            $url = $match[1];
            $event->website = $url;
            $description = trim(str_replace($url, '', $description));
        }

        if($event->field_is_from_ics_invite('description')) {
            $event->description = $description;
        } else {
            $ignored[] = 'description';
        }



        // DATE/TIME

        if($event->field_is_from_ics_invite('datetime')) {

            // If the timezone wasn't set by pulling it out of the event description (or manually later)...
            if(!$event->timezone) {
                // Get the default timezone of this user to localize the dates
                if($user->default_timezone) {
                    $event->timezone = $tz = $user->default_timezone;
                    Log::info('User default timezone '.$tz);
                } else {
                    $tz = 'UTC';
                    Log::info('Timezone '.$tz);
                }
            }

            $tz = new DateTimeZone($tz);

            $start = new DateTime($data->dtstart);
            $end = new DateTime($data->dtend);

            $start->setTimeZone($tz);
            $end->setTimeZone($tz);

            Log::info($start->format('c').' '.$end->format('c'));

            if($start->format('Y-m-d') == $end->format('Y-m-d')) {
                $event->start_date = $start->format('Y-m-d');
                $event->start_time = $start->format('H:i:s');
                if($end)
                    $event->end_time = $end->format('H:i:s');
            } else {
                $event->start_date = $start->format('Y-m-d');
                $event->end_date = $end->format('Y-m-d');
            }

            $event->sort_date = $event->sort_date();

        } else {
            $ignored[] = 'datetime';
        }

        // TODO: Geocode the location from the invite to populate all the fields



        $event->status = 'confirmed';
        $event->unlisted = 0;

        $event->save();

        // Store a snapshot in the revision table
        $revision = EventRevision::createFromEvent($event);
        $revision->edit_summary = 'Updated via calendar invite';
        $revision->save();

        $this->log_inbound_email(($is_new ? 'created' : 'updated'), $raw_data, $ics, $user, $event);

        if($is_new) {
            event(new EventCreated($event));
        } else {
            event(new EventUpdated($event, $revision));
        }

        return response()->json([
            'result' => 'success',
            'event_id' => $event->id,
            'revision_id' => $revision->id
        ]);
    }

    private function log_inbound_email($status, $raw, $ics, $user=null, $event=null) {
        $log = new InboundEmail();
        $log->status = $status;
        $log->raw_ics = $ics;
        $log->raw_body = $raw;
        if($user)
            $log->user_id = $user->id;
        if($event)
            $log->event_id = $event->id;
        $log->save();
    }

}
