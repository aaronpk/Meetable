<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\EventRevision, App\Tag, App\Response, App\ResponsePhoto, App\Setting;
use Illuminate\Support\Str;
use Auth, Storage, Gate, Log, DB;
use Image;
use DateTime;
use App\Services\Zoom, App\Services\EventParser;
use App\Events\EventCreated, App\Events\EventUpdated;

class EventController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function new_event() {
        Gate::authorize('create-event');

        $event = null;

        // If a URL is given in the query string, fetch that URL and look for event data, pre-populating the fields here
        if(request('url')) {
            $event = EventParser::eventFromURL(request('url'));
        }

        if(!$event) {
            $event = new Event;
        }

        $parent = null;
        if(request('parent')) {
            $event->parent = Event::where('id', request('parent'))->first();
        }

        if(Setting::value('default_coc_url')) {
            $event->code_of_conduct_url = Setting::value('default_coc_url');
        }

        return view('edit-event', [
            'event' => $event,
            'mode' => 'create',
            'form_action' => route('create-event'),
        ]);
    }

    public function import_event() {
        Gate::authorize('create-event');

        return view('import-event', [
            'form_action' => route('new-event'),
        ]);
    }

    public function create_event(Request $request) {
        Gate::authorize('create-event');

        // Check for required fields: name, start_date
        $request->validate([
            'name' => 'required',
            'start_date' => 'required|date_format:Y-m-d',
            'status' => 'in:'.implode(',', array_keys(Event::$STATUSES)),
        ]);

        $event = new Event();
        $event->name = request('name');

        $event->generate_random_values();

        if(request('is_template')) {
            $event->is_template = true;
            $event->recurrence_interval = request('recurrence_interval');
            $event->key = '';
        }

        $event->slug = Event::slug_from_name($event->name);

        $event->location_name = request('location_name') ?: '';

        // When cloning an event, the address details will be provided
        $event->location_address = request('location_address') ?: '';
        $event->location_locality = request('location_locality') ?: '';
        $event->location_region = request('location_region') ?: '';
        $event->location_country = request('location_country') ?: '';

        $event->latitude = request('latitude') ?: null;
        $event->longitude = request('longitude') ?: null;
        $event->timezone = request('timezone') ?: '';

        $event->start_date = date('Y-m-d', strtotime(request('start_date')));
        if(request('end_date'))
            $event->end_date = date('Y-m-d', strtotime(request('end_date')));
        if(request('start_time'))
            $event->start_time = date('H:i:00', strtotime(request('start_time')));
        if(request('end_time'))
            $event->end_time = date('H:i:00', strtotime(request('end_time')));

        $event->sort_date = $event->sort_date();

        $event->status = request('status');

        $event->summary = request('summary');
        $event->description = request('description');
        $event->website = request('website');
        $event->tickets_url = request('tickets_url');
        $event->code_of_conduct_url = request('code_of_conduct_url');
        $event->meeting_url = request('meeting_url');
        $event->video_url = request('video_url');
        $event->notes_url = request('notes_url');

        $event->cover_image = request('cover_image');

        $event->unlisted = request('unlisted') ?: 0;

        $event->created_by = Auth::user()->id;
        $event->last_modified_by = Auth::user()->id;

        $event->hide_from_main_feed = request('hide_from_main_feed') ?: 0;
        $event->parent_id = request('parent_id');

        $event->cloned_from_id = request('cloned_from_id') ?: null;
        $event->previous_instance_date = request('previous_instance_date') ?: null;

        // Schedule a zoom meeting if requested
        if(request('create_zoom_meeting')) {
            $meeting_result = $event->schedule_zoom_meeting();
            if(!$meeting_result) {
                back()->withInput()->withErrors(['Failed to create the Zoom meeting. The changes were not saved.']);
            }
        }

        $event->save();

        foreach(explode(' ', request('tags')) as $t) {
            if(trim($t))
                $event->tags()->attach(Tag::get($t));
        }

        // Store a snapshot in the revision table
        $revision = EventRevision::createFromEvent($event);
        $revision->save();

        event(new EventCreated($event));

        if($event->is_template) {
            $event->create_upcoming_recurrences();
            return redirect(route('templates'));
        } else {
            return redirect($event->permalink());
        }
    }

    public function delete_event(Event $event) {
        Gate::authorize('manage-event', $event);

        if($event->is_template) {
            $event->delete_upcoming_recurrences();
        }

        $event->delete();
        return redirect('/');
    }

    public function edit_event(Event $event) {
        Gate::authorize('manage-event', $event);

        return view('edit-event', [
            'event' => $event,
            'mode' => 'edit',
            'action_heading' => ($event->recurrence_interval ? 'Edit Recurring' : 'Editing'),
            'form_action' => route('save-event', $event),
        ]);
    }

    public function clone_event(Event $event) {
        Gate::authorize('create-event');

        if(!Setting::value('clone_meeting_url')) {
            $event->meeting_url = '';
        }

        // Predict the next recurrence of the event based on the past occurrence
        if($event->previous_instance_date) {
            $pastDate = new DateTime($event->previous_instance_date);
            $currentDate = new DateTime($event->start_date);
            $diff = $pastDate->diff($currentDate, true);
            $nextDate = $currentDate->add($diff);
            $event->start_date = $nextDate->format('Y-m-d');
        }

        return view('edit-event', [
            'event' => $event,
            'mode' => 'clone',
            'action_heading' => 'Cloning',
            'form_action' => route('create-event'),
        ]);
    }

    public function recurring_event(Event $event) {
        Gate::authorize('create-event');

        // Predict the next recurrence of the event based on the past occurrence
        if($event->previous_instance_date) {
            $pastDate = new DateTime($event->previous_instance_date);
            $currentDate = new DateTime($event->start_date);
            $diff = $pastDate->diff($currentDate, true);
            $nextDate = $currentDate->add($diff);
            $event->start_date = $nextDate->format('Y-m-d');
        }

        return view('edit-event', [
            'event' => $event,
            'mode' => 'recurring',
            'action_heading' => 'Create Recurring',
            'form_action' => route('create-event'),
        ]);
    }

    public function recurring_event_details(Request $request, Event $event) {
        Gate::authorize('manage-event', $event);

        $recurrence = request('recurrence');
        $date = new DateTime(request('date'));



        return view('recurring-event-details', [
            'event' => $event,
            'recur_month_date' => $date->format('M j'),
            'recur_date' => $date->format('jS'),
            'recur_dow' => $date->format('l'),
        ]);
    }

    public function save_event(Request $request, Event $event) {
        Gate::authorize('manage-event', $event);

        $request->validate([
            'name' => 'required',
            'start_date' => 'required|date_format:Y-m-d',
            'status' => 'in:'.implode(',', array_keys(Event::$STATUSES)),
        ]);

        if($event->fields_from_ics) {
            // Remove edited fields from the list of fields created by an ICS invite

            $fields = json_decode($event->fields_from_ics);

            if(request('name') != $event->name) {
                $fields = array_diff($fields, ['name']);
            }

            if(request('description') != $event->description) {
                $fields = array_diff($fields, ['description']);
            }

            $date_fields = ['start_date', 'end_date', 'start_time', 'end_time'];
            foreach($date_fields as $f) {
                if(request($f) != $event->{$f}) {
                    $fields = array_diff($fields, ['datetime']);
                }
            }

            $loc_fields = ['location_name', 'location_address', 'location_locality', 'location_region', 'location_country'];
            foreach($loc_fields as $f) {
                if(request($f) != $event->{$f}) {
                    $fields = array_diff($fields, ['location']);
                }
            }

            $event->fields_from_ics = json_encode(array_values($fields));
        }


        // Update the properties on the event
        foreach(Event::$EDITABLE_PROPERTIES as $p) {
            $event->{$p} = (request($p) ?: null);
        }

        // If there is an end date, remove the start/end times
        if($event->end_date) {
            $event->start_time = null;
            $event->end_time = null;
        }

        if(!$event->unlisted)
            $event->unlisted = 0; // override null from above

        if(!$event->hide_from_main_feed)
            $event->hide_from_main_feed = 0;

        $event->sort_date = $event->sort_date();

        // Generate a new slug
        $event->slug = Event::slug_from_name($event->name);

        // Schedule a zoom meeting if requested
        if(request('create_zoom_meeting')) {
            $meeting_result = $event->schedule_zoom_meeting();
            if(!$meeting_result) {
                back()->withInput()->withErrors(['Failed to create the Zoom meeting. The changes were not saved.']);
            }
        } elseif($event->zoom_meeting_id) {
            $event->update_zoom_meeting();
        }

        // Allow event templates to change the recurrence property
        if($event->is_template) {
            $event->recurrence_interval = request('recurrence_interval');
        }

        $event->last_modified_by = Auth::user()->id;
        $event->save();

        // Capture the tags serialized as JSON
        $rawtags = request('tags') ? explode(' ', request('tags')) : [];
        $tags = [];
        foreach($rawtags as $t) {
            if(trim($t)) {
                $tags[] = Tag::get($t);
            }
        }

        // Delete related tags
        $event->tags()->detach();
        // Add all the tags back
        foreach($tags as $tag) {
            $event->tags()->attach($tag);
        }

        $revision = EventRevision::createFromEvent($event);
        $revision->edit_summary = request('edit_summary');
        $revision->save();

        event(new EventUpdated($event, $revision));

        if($event->is_template) {
            $event->delete_upcoming_recurrences();
            $event->create_upcoming_recurrences();
            return redirect(route('templates'));
        } else {
            return redirect($event->permalink());
        }
    }

    public function revision_history(Event $event) {
        Gate::authorize('manage-event', $event);

        $revisions = $event->revisions;

        return view('revision-history', [
            'event' => $event,
            'revisions' => $revisions,
        ]);
    }

    public function view_revision(Event $event, EventRevision $revision) {
        Gate::authorize('manage-event', $revision);

        $date = new DateTime($revision->start_date);

        return view('event', [
            'event' => $revision,
            'year' => $date->format('Y'),
            'month' => $date->format('m'),
            'key' => $revision->key,
            'slug' => $revision->slug,
            'mode' => 'archive',
            'event_id' => $event->id,
        ]);
    }

    public function view_revision_diff(Event $event, EventRevision $revision) {
        Gate::authorize('manage-event', $revision);

        $previous = EventRevision::where('event_id', $revision->event_id)
          ->where('id', '!=', $revision->id)
          ->where('created_at', '<', $revision->created_at)
          ->orderBy('created_at', 'desc')
          ->first();

        return view('diff', [
            'current' => $revision,
            'previous' => $previous,
            'event_id' => $event->id,
        ]);
    }

    public function upload_event_cover_image(Event $event) {
        Gate::authorize('manage-event', $event);

        if(!request()->hasFile('image')) {
            return response()->json(['error'=>'missing file']);
        }

        $image = Image::make(request('image'));

        $image->fit(1440, 640);

        // Save the resized cover photo in the storage folder
        $filename = 'public/events/'.date('Ymd').'-'.Str::random(30).'.jpg';

        Storage::put($filename, $image->stream('jpg', 80), 'public');
        $photo_url = Storage::url($filename);

        return response()->json([
            'url' => $photo_url,
        ]);
    }

    public function add_event_photo(Event $event) {
        Gate::authorize('manage-event', $event);

        return view('add-event-photo', [
            'event' => $event,
        ]);
    }

    public function upload_event_photo(Event $event) {
        Gate::authorize('manage-event', $event);

        if(!request()->hasFile('photo')) {
            return redirect($event->permalink().'#error');
        }

        // Create a new stub response to store this photo
        $response = new Response;
        $response->event_id = $event->id;
        $response->approved = true;
        $response->approved_by = Auth::user()->id;
        $response->approved_at = date('Y-m-d H:i:s');
        $response->created_by = Auth::user()->id;
        $response->save();

        $ext = 'jpg';
        // If a png is uploaded, change the extension to png
        if(request('photo')->extension() == 'png')
            $ext = 'png';

        $filename = Str::random(30).'.'.$ext;
        $full_filename = 'public/responses/'.$event->id.'/'.$filename;

        // Save a copy of the file in the download folder
        Storage::putFileAs('public/responses/'.$event->id, request('photo'), $filename, 'public');
        $photo_url = Storage::url($full_filename);

        Log::info('Uploaded file stored as '.$full_filename.' at URL '.$photo_url);

        $photo = ResponsePhoto::create($response, [
            'original_filename' => $full_filename,
            'original_url' => $photo_url,
            'alt' => request('alt'),
        ]);

        $photo->createResizedImages(Image::make(request('photo')));

        return redirect($event->permalink().'#photos');
    }

    public function set_photo_order(Event $event) {
        Gate::authorize('manage-event', $event);

        foreach(request('order') as $order=>$photo_id) {
            $photo = ResponsePhoto::where('event_id', $event->id)->where('id', $photo_id)->first();
            $photo->sort_order = $order;
            $photo->save();
        }

        return response()->json([
            'result' => 'ok'
        ]);
    }

    public function get_timezone() {
        // Return timezone for the given lat/lng
        $timezone = \p3k\Timezone::timezone_for_location(request('latitude'), request('longitude'));
        return response()->json([
            'timezone' => $timezone,
        ]);
    }

    public function unlisted_events() {
        Gate::authorize('create-event');

        $events = Event::select(DB::raw('YEAR(start_date) as year'), DB::raw('MONTH(start_date) AS month'),
            'start_date', 'end_date', 'start_time', 'end_time', 'slug', 'key', 'name', 'status')
          ->where('unlisted', 1)
          ->where('is_template', 0)
          ->orderBy('sort_date', 'desc')
          ->get();

        $data = [];

        foreach($events as $event) {
            if(!isset($data[$event->year]))
                $data[$event->year] = [];

            if(!isset($data[$event->year][$event->month]))
                $data[$event->year][$event->month] = [];

            $data[$event->year][$event->month][] = $event;
        }

        return view('unlisted', [
            'data' => $data,
            'page_title' => 'Unlisted Events',
        ]);
    }

    public function template_events() {
        Gate::authorize('create-event');

        $events = Event::select('id', 'recurrence_interval', 'start_date', 'start_time', 'end_time', 'name')
          ->where('is_template', 1)
          ->orderBy('sort_date', 'desc')
          ->get();

        foreach($events as $event) {
            $instances[$event->id] = Event::select('id', 'start_date', 'start_time', 'end_time', 'name', 'slug', 'key')
              ->where('created_from_template_event_id', $event->id)
              ->where('start_date', '>', date('Y-m-d'))
              ->orderBy('sort_date', 'asc')
              ->get();
        }

        return view('templates', [
            'events' => $events,
            'instances' => $instances,
            'page_title' => 'Recurring Events',
        ]);
    }

    public function edit_registration(Event $event) {
        Gate::authorize('create-event');



        return view('edit-registration', [
            'event' => $event,
        ]);
    }

}
