<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\EventRevision, App\Tag, App\Response;
use Illuminate\Support\Str;
use Auth, Storage, Gate;


class EventController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function new_event() {
        Gate::authorize('create-event');

        $event = new Event;
        return view('edit-event', [
            'event' => $event,
            'mode' => 'create',
            'form_action' => route('create-event'),
        ]);
    }

    public function create_event() {
        Gate::authorize('create-event');

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

        $event->description = request('description');
        $event->website = request('website');
        $event->tickets_url = request('tickets_url');

        $event->created_by = Auth::user()->id;
        $event->last_modified_by = Auth::user()->id;

        $event->save();

        foreach(explode(' ', request('tags')) as $t) {
            if(trim($t))
                $event->tags()->attach(Tag::get($t));
        }

        // If there was a cover photo added, move it to the permanent location and add to the event
        if($from = request('cover_image')) {
            if(preg_match('/^public\/events\/(temp|\d+)\/[a-zA-Z0-9]+\.jpg$/', $from, $match)) {
                $fn = basename($from);
                $filename = 'public/events/'.$event->id.'/'.$fn;
                if($match[1] == 'temp')
                    Storage::move($from, $filename);
                else
                    Storage::copy($from, $filename);
                $event->cover_image = $filename;
                $event->save();
            }
        }

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

    public function save_event(Event $event) {
        Gate::authorize('manage-event', $event);

        $properties = [
            'name', 'start_date', 'end_date', 'start_time', 'end_time',
            'location_name', 'location_address', 'location_locality', 'location_region', 'location_country',
            'latitude', 'longitude', 'timezone',
            'website', 'tickets_url', 'description',
        ];

        // Save a snapshot of the previous state
        $revision = new EventRevision;
        $fixed_properties = ['key','slug','created_by','last_modified_by','photo_order','cover_image'];
        foreach(array_merge($properties, $fixed_properties) as $p) {
            $revision->{$p} = $event->{$p} ?: null;
        }
        $revision->save();

        // Update the properties on the event
        foreach($properties as $p) {
            $event->{$p} = request($p) ?: null;
        }

        // Handle cover image, only modify if a new photo was added in the temp folder
        if($from = request('cover_image')) {
            if(preg_match('/^public\/events\/temp\/[a-zA-Z0-9]+\.jpg$/', $from)) {
                // If it doesn't match a temp name, then it is the same image it was previously
                $fn = basename($from);
                $filename = 'public/events/'.$event->id.'/'.$fn;
                Storage::move($from, $filename);
                $event->cover_image = $filename;
            }
        } else {
            $event->cover_image = null;
        }

        // Generate a new slug
        $event->slug = Event::slug_from_name($event->name);

        $event->last_modified_by = Auth::user()->id;
        $event->save();

        // Delete related tags
        $event->tags()->detach();
        // Add all the tags back
        foreach(explode(' ', request('tags')) as $t) {
            if(trim($t))
                $event->tags()->attach(Tag::get($t));
        }

        return redirect($event->permalink());
    }

    public function upload_event_cover_image(Event $event) {
        Gate::authorize('manage-event', $event);

        if(!request()->hasFile('image')) {
            return response()->json(['error'=>'missing file']);
        }

        // Save a copy of the file in the download folder
        $filename = Str::random(30).'.jpg';

        request('image')->storeAs('public/events/temp/', $filename);
        $photo_url = 'public/events/temp/'.$filename;

        return response()->json([
            'url' => $photo_url,
            'cropped' => Event::image_proxy($photo_url, '1440x640,sc'),
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

        // Save a copy of the file in the download folder
        $filename = Str::random(30).'.jpg';

        request('photo')->storeAs('public/responses/'.$event->id, $filename);
        $photo_url = 'public/responses/'.$event->id.'/'.$filename;

        // Create a new stub response to store this photo
        $response = new Response;
        $response->event_id = $event->id;
        $response->approved = true;
        $response->approved_by = Auth::user()->id;
        $response->approved_at = date('Y-m-d H:i:s');
        $response->created_by = Auth::user()->id;
        $response->photos = [$photo_url];

        if(request('alt')) {
            $response->photo_alt = [
                $photo_url => request('alt')
            ];
        }

        $response->save();

        return redirect($event->permalink().'#photos');
    }

    public function set_photo_order(Event $event) {
        Gate::authorize('manage-event', $event);

        $event->photo_order = request('order');
        $event->save();

        return response()->json([
            'result' => 'ok'
        ]);
    }

    public function edit_responses(Event $event) {
        Gate::authorize('manage-event', $event);

        $responses = $event->responses()->get();

        return view('edit-responses', [
            'event' => $event,
            'responses' => $responses,
        ]);
    }

    public function get_response_details(Event $event, Response $response) {
        Gate::authorize('manage-event', $event);
        return response()->json($response);
    }

    public function delete_response(Event $event, Response $response) {
        Gate::authorize('manage-event', $event);

        $id = $response->id;
        $response->delete();
        return response()->json([
            'result' => 'ok',
            'response_id' => $id,
        ]);
    }

    public function save_alt_text(Event $event) {
        Gate::authorize('manage-event', $event);

        $response = Response::where('event_id', $event->id)->where('id', request('response_id'))->first();

        if(!$response) {
            return response()->json([
                'result' => 'error',
            ]);
        }

        $response->set_photo_alt(request('url'), request('alt'));
        $response->save();

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
