<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\EventRevision, App\Tag, App\Response, App\ResponsePhoto, App\Setting;
use Illuminate\Support\Str;
use Auth, Storage, Gate, Log;
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

        $event->key = Str::random(12);
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

        $event->description = request('description');
        $event->website = request('website');
        $event->tickets_url = request('tickets_url');
        $event->code_of_conduct_url = request('code_of_conduct_url');
        $event->meeting_url = request('meeting_url');

        $event->cover_image = request('cover_image');

        $event->created_by = Auth::user()->id;
        $event->last_modified_by = Auth::user()->id;

        // Schedule a Zoom meeting
        if(Setting::value('zoom_api_key') && request('create_zoom_meeting')) {
            $event->meeting_url = Zoom::schedule_meeting($event);
            if(!$event->meeting_url) {
                return back()->withInput()->withErrors(['Failed to create the Zoom meeting. The event was not saved.']);
            }
        }

        $event->save();

        foreach(explode(' ', request('tags')) as $t) {
            if(trim($t))
                $event->tags()->attach(Tag::get($t));
        }

        event(new EventCreated($event));

        return redirect($event->permalink());
    }

    public function delete_event(Event $event) {
        Gate::authorize('manage-event', $event);

        $event->delete();
        return redirect('/');
    }

    public function edit_event(Event $event) {
        Gate::authorize('manage-event', $event);

        return view('edit-event', [
            'event' => $event,
            'mode' => 'edit',
            'form_action' => route('save-event', $event),
        ]);
    }

    public function clone_event(Event $event) {
        Gate::authorize('create-event');

        return view('edit-event', [
            'event' => $event,
            'mode' => 'clone',
            'form_action' => route('create-event'),
        ]);
    }

    public function save_event(Request $request, Event $event) {
        Gate::authorize('manage-event', $event);

        $request->validate([
            'name' => 'required',
            'start_date' => 'required|date_format:Y-m-d',
            'status' => 'in:'.implode(',', array_keys(Event::$STATUSES)),
        ]);

        // Save a snapshot of the previous state
        $revision = new EventRevision;
        $revision->event_id = $event->id;

        // Update the properties on the event
        foreach(Event::$EDITABLE_PROPERTIES as $p) {
            $event->{$p} = $revision->{$p} = (request($p) ?: null);
        }

        $event->sort_date = $revision->sort_date = $event->sort_date();

        // Generate a new slug
        $event->slug = $revision->slug = Event::slug_from_name($event->name);

        // Schedule a zoom meeting if requested
        if(Setting::value('zoom_api_key') && request('create_zoom_meeting')) {
            $event->meeting_url = $revision->meeting_url = Zoom::schedule_meeting($event);
            if(!$event->meeting_url) {
                return back()->withInput()->withErrors(['Failed to create the Zoom meeting. The changes were not saved.']);
            }
        }

        $event->last_modified_by = $revision->last_modified_by = Auth::user()->id;
        $event->save();

        $revision->created_by = $event->created_by;

        // Capture the tags serialized as JSON
        $rawtags = request('tags') ? explode(' ', request('tags')) : [];
        $tags = [];
        $tags_string = [];
        foreach($rawtags as $t) {
            if(trim($t)) {
                $tag = Tag::get($t);
                $tags[] = $tag;
                $tags_string[] = $tag->tag;
            }
        }

        $revision->key = $event->key;
        $revision->tags = json_encode($tags_string);
        $revision->edit_summary = request('edit_summary');
        $revision->save();

        // Delete related tags
        $event->tags()->detach();
        // Add all the tags back
        foreach($tags as $tag) {
            $event->tags()->attach($tag);
        }

        event(new EventUpdated($event, $revision));

        return redirect($event->permalink());
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

    public function save_alt_text(Event $event) {
        Gate::authorize('manage-event', $event);

        $photo = ResponsePhoto::where('event_id', $event->id)->where('id', request('photo_id'))->first();

        if(!$photo) {
            return response()->json([
                'result' => 'error',
            ]);
        }

        $photo->alt = request('alt');
        $photo->save();

        return response()->json([
            'result' => 'ok',
        ]);
    }

    public function get_timezone() {
        // Return timezone for the given lat/lng
        $timezone = \p3k\Timezone::timezone_for_location(request('latitude'), request('longitude'));
        return response()->json([
            'timezone' => $timezone,
        ]);
    }

}
