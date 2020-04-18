<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\EventRevision, App\Tag, App\Response, App\ResponsePhoto;
use Illuminate\Support\Str;
use Auth, Storage, Gate, Log;
use Image;


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

    public function create_event(Request $request) {
        Gate::authorize('create-event');

        // Check for required fields: name, start_date
        $request->validate([
            'name' => 'required',
            'start_date' => 'required|date_format:Y-m-d'
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

        $event->description = request('description');
        $event->website = request('website');
        $event->tickets_url = request('tickets_url');
        $event->code_of_conduct_url = request('code_of_conduct_url');

        $event->cover_image = request('cover_image');

        $event->created_by = Auth::user()->id;
        $event->last_modified_by = Auth::user()->id;

        $event->save();

        foreach(explode(' ', request('tags')) as $t) {
            if(trim($t))
                $event->tags()->attach(Tag::get($t));
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
            'website', 'tickets_url', 'code_of_conduct_url', 'description', 'cover_image',
        ];

        // Save a snapshot of the previous state
        $revision = new EventRevision;
        $fixed_properties = ['key','slug','created_by','last_modified_by'];
        foreach(array_merge($properties, $fixed_properties) as $p) {
            $revision->{$p} = $event->{$p} ?: null;
        }
        $revision->save();

        // Update the properties on the event
        foreach($properties as $p) {
            $event->{$p} = request($p) ?: null;
        }

        $event->sort_date = $event->sort_date();

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
