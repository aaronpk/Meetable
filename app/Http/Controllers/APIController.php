<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Event, App\EventRevision, App\Response, App\User;
use App\Events\EventCreated, App\Events\EventUpdated;
use App\Events\WebmentionReceived;
use Illuminate\Support\Str;
use Auth, Gate;
use DateTime, DateTimeZone, Exception;
use p3k\XRay;

class APIController extends BaseController
{
    public function add_event(Request $request) {
        Gate::authorize('create-event');

        if(!request('name')) {
            return $this->error('missing name');
        }

        if(!request('start_date')) {
            return $this->error('missing start date');
        }

        try {
            $start = new DateTime(request('start_date'));
        } catch(Exception $e) {
            return $this->error('invalid start date');
        }

        if(request('end_date')) {
            try {
                $end = new DateTime(request('end_date'));
            } catch(Exception $e) {
                return $this->error('invalid end date');
            }
        }

        if(request('timezone')) {
            try {
                $tz = new DateTimeZone(request('timezone'));
            } catch(Exception $e) {
                return $this->error('invalid timezone');
            }
        }

        $event = new Event();
        $event->name = request('name');

        $event->key = Str::random(12);
        $event->slug = Event::slug_from_name($event->name);

        $event->location_name = request('location_name') ?: '';
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

        $event->status = request('status') ?: 'confirmed';

        $event->description = request('description');
        $event->website = request('website');
        $event->tickets_url = request('tickets_url');
        $event->code_of_conduct_url = request('code_of_conduct_url');
        $event->meeting_url = request('meeting_url');
        $event->video_url = request('video_url');

        $event->cover_image = request('cover_image');

        $event->unlisted = request('unlisted') ?: 0;

        $event->created_by = Auth::user()->id;
        $event->last_modified_by = Auth::user()->id;

        $event->save();

        foreach(explode(' ', request('tags')) as $t) {
            if(trim($t))
                $event->tags()->attach(Tag::get($t));
        }

        // Store a snapshot in the revision table
        $revision = EventRevision::createFromEvent($event);
        $revision->save();

        event(new EventCreated($event));

        return response()->json([
            'url' => $event->absolute_permalink()
        ]);
    }


    public function add_response() {

        $event_url = request('event');
        $url = request('url');

        $event = Event::find_from_url($event_url);

        if(!$event) {
            return $this->error('Event not found');
        }

        // Check if this response was already received via webmention and reject if so
        $response = $event->responses()->where('source_url', $url)->first();

        if($response) {
            return $this->error('That URL was already sent via Webmention');
        }

        $xray = new XRay();

        $opts = [];

        if(parse_url($url, PHP_URL_HOST) == 'twitter.com' && env('TWITTER_CONSUMER_KEY')) {
            $opts['twitter_api_key'] = env('TWITTER_CONSUMER_KEY');
            $opts['twitter_api_secret'] = env('TWITTER_CONSUMER_SECRET');
            $opts['twitter_access_token'] = env('TWITTER_ACCESS_TOKEN');
            $opts['twitter_access_token_secret'] = env('TWITTER_ACCESS_TOKEN_SECRET');
        }

        $data = $xray->parse($url, $opts);

        if(isset($data['error'])) {
            return $this->error($data['error_description']);
        }

        $sourceData = $data['data'];

        $response = $event->responses()->where('url', $url)->first();

        if(!$response) {
            $response = new Response;
            $response->event_id = $event->id;
            $response->approved = true;
            $response->approved_by = Auth::user()->id;
            $response->approved_at = date('Y:m:d H:i:s');
            if(Auth::user()->is_admin) {
                // Allow admin users to override the created_by to other users
                $by = Auth::user()->id;
                if(request('by')) {
                    if($u = User::where('url', request('by'))->first())
                        $by = $u->id;
                }
                $response->created_by = $by;
            } else {
                $response->created_by = Auth::user()->id;
            }
        }

        \App\Services\ExternalResponse::setResponsePropertiesFromXRayData($response, $sourceData, $url, $event_url);

        // Override url to the url we fetched it from rather than what the page reports
        $response->url = $url;

        $response->save();

        if(isset($sourceData['photo']))
            \App\Services\ExternalResponse::setPhotoRecords($response, $sourceData['photo']);

        event(new WebmentionReceived($response));

        return response()->json([
            'data' => $response
        ]);
    }

    public function user(Request $request) {
        return $request->user();
    }

    private function error($error, $code=400) {
        return response()->json([
            'error' => $error
        ], $code);
    }
}
