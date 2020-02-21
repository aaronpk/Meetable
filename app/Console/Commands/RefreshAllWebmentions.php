<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Log, Storage;
use App\User, App\Event, App\Response;
use Image;
use p3k\XRay;

class RefreshAllWebmentions extends Command {

    protected $signature = 'webmention:refresh';
    protected $description = 'Fetch all webmentions again and re-parse';

    public function handle() {

        $webmentions = Response::where('source_url', '!=' ,'')->get();
        foreach($webmentions as $webmention) {

            $this->info($webmention->source_url);

            $event = Event::where('id', $webmention->event_id)->first();

            $targetURL = $event->absolute_permalink();

            $xray = new XRay();
            $data = $xray->parse($webmention->source_url, [
                'target' => $targetURL,
            ]);

            if(isset($data['error'])) {
                // something changed
                $this->error("Webmention failed to parse: ".$data['error']);
                continue;
            }

            $source = $data['data'];

            // Don't override the author photo otherwise we have to downlaod it again
            $author_photo = $webmention->author_photo;
            \App\Services\ExternalResponse::setResponsePropertiesFromXRayData($webmention, $source, $webmention->source_url, $targetURL);
            $webmention->author_photo = $author_photo;

            $webmention->save();
        }

    }

}
