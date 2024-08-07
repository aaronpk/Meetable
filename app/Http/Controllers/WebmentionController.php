<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\Response, App\User, App\Setting;
use App\Events\WebmentionReceived;
use Illuminate\Support\Str;
use Auth;
use p3k\XRay;


class WebmentionController extends BaseController
{
    public function webmention() {
        if(!Setting::value('enable_webmention_responses'))
            return abort(404);

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

        if($event->status == 'cancelled') {
            return $this->error('Webmentions are not accepted to cancelled events', 200);
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

        // Handle redirects from source URLs
        if(isset($data['url']) && $data['url'] != $sourceURL) {
            // The source URL was a redirect to a new URL. This may happen before or after the new URL has already sent a webmention.

            // Check to see if a webmention has already been received from the new URL
            $response = Response::where('event_id', $event->id)
              ->withTrashed()
              ->where('source_url', $data['url'])->first();
            if($response) {
                // If a webmention from the new URL already exists, delete the webmention from the old URL
                Response::where('event_id', $event->id)
                  ->withTrashed()
                  ->where('source_url', $sourceURL)->delete();
                return response()->json([
                    'result' => 'updated',
                    'description' => 'This source URL redirected to a response that has already been received so this response was deleted'
                ]);
            } else {
                // Check if a webmention has already been received from the old URL
                $response = Response::where('event_id', $event->id)
                  ->withTrashed()
                  ->where('source_url', $sourceURL)->first();
                if($response) {
                    // Update the source URL in the database record
                    $response->source_url = $data['url'];
                    $response->save();
                }
                // If the URL was a redirect, rewrite the source URL to the final URL after the redirect.
                // When the webmention from the new URL comes in later, it will be treated as an update since it already exists.
                $sourceURL = $data['url'];
            }
        }

        $source = $data['data'];

        if(!is_array($source)) {
            return $this->error("There was a problem parsing the source URL");
        }

        // Drop reposts of everything, including reposts of the event and also of responses to the event
        if(isset($source['post-type']) && $source['post-type'] == 'repost') {
            if(request('from') == 'browser') {
                return $this->error('Reposts are not accepted');
            } else {
                return response()->json([
                    'result' => 'rejected',
                ]);
            }
        }

        $response = Response::where('event_id', $event->id)
          ->withTrashed()
          ->where('source_url', $sourceURL)->first();
        if(!$response) {
            $response = new Response;
            $response->event_id = $event->id;
            $response->source_url = $sourceURL;
        } else {
            if($response->trashed()) {
                // Don't allow deleted source URLs to be re-added
                return $this->error("The webmention from this URL has been deleted from the event and won't be added again");
            }
        }

        // Reset approval on updates, requiring moderation again
        $response->approved = false;

        // If the webmention is from a user who has logged in, approve it immediately
        $users = User::where('url', 'like', '%'.parse_url($sourceURL, PHP_URL_HOST).'%')->get();
        foreach($users as $user) {
            if(\p3k\url\host_matches($sourceURL, $user->url)) {
                $response->approved = true;
                $response->approved_at = date('Y-m-d H:i:s');
            }
        }

        \App\Services\ExternalResponse::setResponsePropertiesFromXRayData($response, $source, $sourceURL, $targetURL);

        $response->save();

        \App\Services\ExternalResponse::setPhotoRecords($response, $source['photo'] ?? false);

        event(new WebmentionReceived($response));

        if(request('from') == 'browser') {
            return redirect($event->permalink().'#rsvps');
        } else {
            $data = [
                'result' => 'accepted',
                'data' => json_decode($response->data),
            ];
            if($response->approved == false) {
                $data['status'] = 'Your webmention was received, but was not automatically approved. If you log in with the same domain as your RSVP, it will be automatically approved in the future.';
            }
            return response()->json($data);
        }
    }

    public function get() {
        if(!Setting::value('enable_webmention_responses'))
            return abort(404);

        return view('webmention');
    }

    private function error($error, $code=400) {
        if(request('from') != 'browser') {
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
