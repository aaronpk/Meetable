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

        if(isset($source['published'])) {
            $response->published = date('Y-m-d H:i:s', strtotime($source['published']));
        }

        foreach(['url', 'name'] as $prop) {
            if(isset($source[$prop])) {
                $response->{$prop} = $source[$prop];
            } else {
                $response->{$prop} = null;
            }
        }

        if(isset($source['rsvp'])) {
            $response->rsvp = strtolower($source['rsvp']);
        } else {
            $response->rsvp = null;
        }

        if(isset($source['content']['text'])) {
            $response->content_text = $source['content']['text'];
        } else {
            $response->content_text = null;
        }

        if(isset($source['content']['html'])) {
            $response->content_html = $source['content']['html'];
        } else {
            $response->content_html = null;
        }

        if(isset($source['photo'])) {
            // A background job will be queued to download these photos
            $response->photos = $source['photo'];
        } else {
            $response->photos = null;
        }

        foreach(['name', 'photo', 'url'] as $prop) {
            if(isset($source['author'][$prop])) {
                $response->{'author_'.$prop} = $source['author'][$prop];
            } else {
                $response->{'author_'.$prop} = null;
            }
        }

        if(isset($source['author']['url'])) {
            // Set the rsvp_user_id if source URL domain matches the author URL domain
            if(\p3k\url\host_matches($sourceURL, $source['author']['url'])) {
                // Check if there is a user with this URL
                $rsvpUser = User::where('url', $source['author']['url'])->first();
                if($rsvpUser) {
                    $response->rsvp_user_id = $rsvpUser->id;
                }
            }
        }

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
