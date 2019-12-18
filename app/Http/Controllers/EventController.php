<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\EventRevision, App\Tag, App\Response;
use Illuminate\Support\Str;
use Auth;


class EventController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function new_event() {
        $event = new Event;
        return view('edit-event', [
            'event' => $event,
            'mode' => 'create',
            'form_action' => route('create-event'),
        ]);
    }

    public function create_event() {
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

        $event->start_date = date('Y-m-d', strtotime(request('start_date')));
        if(request('end_date'))
            $event->end_date = date('Y-m-d', strtotime(request('end_date')));
        if(request('start_time'))
            $event->start_time = date('H:i:00', strtotime(request('start_time')));
        if(request('end_time'))
            $event->end_time = date('H:i:00', strtotime(request('end_time')));

        $event->description = request('description');
        $event->website = request('website');

        $event->created_by = Auth::user()->id;
        $event->last_modified_by = Auth::user()->id;

        $event->save();

        foreach(explode(' ', request('tags')) as $t) {
            $event->tags()->attach(Tag::get($t));
        }

        return redirect($event->permalink());
    }

    public function delete_event(Event $event) {
        $event->delete();
        return redirect('/');
    }

    public function edit_event(Event $event) {
        return view('edit-event', [
            'event' => $event,
            'mode' => 'edit',
            'form_action' => route('save-event', $event),
        ]);
    }

    public function clone_event(Event $event) {
        return view('edit-event', [
            'event' => $event,
            'mode' => 'clone',
            'form_action' => route('create-event'),
        ]);
    }

    public function save_event(Event $event) {
        $properties = [
            'name', 'start_date', 'end_date', 'start_time', 'end_time',
            'location_name', 'location_address', 'location_locality', 'location_region', 'location_country',
            'website', 'description'
        ];

        // Save a snapshot of the previous state
        $revision = new EventRevision;
        foreach(array_merge($properties, ['key','slug','created_by','last_modified_by']) as $p) {
            $revision->{$p} = $event->{$p} ?: null;
        }
        $revision->save();

        // Update the properties on the event
        foreach($properties as $p) {
            $event->{$p} = request($p) ?: null;
        }

        // Generate a new slug
        $event->slug = Event::slug_from_name($event->name);

        $event->last_modified_by = Auth::user()->id;
        $event->save();

        // Delete related tags
        $event->tags()->detach();
        // Add all the tags back
        foreach(explode(' ', request('tags')) as $t) {
            $event->tags()->attach(Tag::get($t));
        }

        return redirect($event->permalink());
    }

    public function add_event_photo(Event $event) {
        return view('add-event-photo', [
            'event' => $event,
        ]);
    }

    public function upload_event_photo(Event $event) {
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
        $response->created_by = Auth::user()->id;
        $response->photos = [$photo_url];
        $response->save();

        return redirect($event->permalink().'#photos');
    }

    public function set_photo_order(Event $event) {
        $event->photo_order = request('order');
        $event->save();

        return response()->json([
            'result' => 'ok'
        ]);
    }

    public function edit_responses(Event $event) {
        $responses = $event->responses()->get();

        return view('edit-responses', [
            'event' => $event,
            'responses' => $responses,
        ]);
    }
}
