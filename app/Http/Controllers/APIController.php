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

        // TODO: add twitter credentials here if configured
        $data = $xray->parse($url);

        if(isset($data['error'])) {
            return $this->error($data['error_description']);
        }

        $source = $data['data'];

        $response = $event->responses()->where('url', $url)->first();

        if(!$response) {
            $response = new Response;
            $response->event_id = $event->id;
            $response->created_by = Auth::user()->id;
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

    private function error($error, $code=400) {
        return response()->json([
            'error' => $error
        ], $code);
    }
}
