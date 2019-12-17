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


class WebmentionController extends BaseController
{
    public function webmention() {

        // Process synchronously for now so people have an easier time debuggong,
        // move to async if it becomes a problem

        $targetURL = request('target');

        $targetURLHost = parse_url($targetURL, PHP_URL_HOST);
        if(!$targetURLHost) {
            return $this->error('Invalid target URL');
        }

        $event = Event::find_from_url($targetURL);

        if(!$event) {
            return $this->error('Could not find event from target URL');
        }

        $sourceURL = request('source');

        $xray = new XRay();
        $data = $xray->parse($sourceURL, [
            'target' => $targetURL,
        ]);

        // XRay tells us if the URL didn't link to the target
        if(isset($data['error'])) {
            return $this->error($data['error_description']);
        }

        $source = $data['data'];

        if(!is_array($source)) {
            return $this->error("There was a problem parsing the source URL");
        }

        $response = $event->responses()->where('source_url', $sourceURL)->first();
        if(!$response) {
            $response = new Response;
            $response->event_id = $event->id;
            $response->source_url = $sourceURL;
        }

        \App\Services\ExternalResponse::setResponsePropertiesFromXRayData($response, $source, $sourceURL);

        $response->save();

        event(new WebmentionReceived($response));

        return redirect($event->permalink().'#rsvps');
    }

    public function get() {
        return view('webmention');
    }

    private function error($error, $code=400) {
        return view('webmention', [
            'error' => $error,
            'source' => request('source'),
            'target' => request('target'),
        ]);
    }
}
