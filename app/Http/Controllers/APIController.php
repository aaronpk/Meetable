<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\Response, App\User;
use App\Events\WebmentionReceived;
use Illuminate\Support\Str;
use Auth;
use p3k\XRay;

class APIController extends BaseController
{

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

        $source = $data['data'];

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

        \App\Services\ExternalResponse::setResponsePropertiesFromXRayData($response, $source, $url);

        // Override url to the url we fetched it from rather than what the page reports
        $response->url = $url;

        $response->save();

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
