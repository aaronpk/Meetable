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
            return $this->error('Target URL was not a valid event URL. Webmentions are only supported to event URLs.', 200);
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

        $response = $event->responses()->withTrashed()->where('source_url', $sourceURL)->first();
        if(!$response) {
            $response = new Response;
            $response->event_id = $event->id;
            $response->source_url = $sourceURL;

            // If the webmention is from a user who has logged in, approve it immediately
            $users = User::where('url', 'like', '%aaronparecki.com%')->get();
            foreach($users as $user) {
                if(\p3k\url\host_matches($sourceURL, $user->url)) {
                    $response->approved = true;
                    $response->approved_at = date('Y-m-d H:i:s');
                }
            }

        } else {
            if($response->trashed()) {
                // Don't allow deleted source URLs to be re-added
                return $this->error("The webmention from this URL has been deleted from the event and won't be added again");
            }
        }

        \App\Services\ExternalResponse::setResponsePropertiesFromXRayData($response, $source, $sourceURL);

        $response->save();

        event(new WebmentionReceived($response));

        if(request()->wantsJson()) {
            return response()->json([
                'result' => 'accepted'
            ]);
        } else {
            return redirect($event->permalink().'#rsvps');
        }
    }

    public function get() {
        return view('webmention');
    }

    private function error($error, $code=400) {
        if(request()->wantsJson()) {
            return response()->json([
                'error' => $error,
                'source' => request('source'),
                'target' => request('target'),
            ], 400);
        } else {
            return view('webmention', [
                'error' => $error,
                'source' => request('source'),
                'target' => request('target'),
            ]);
        }
    }
}
