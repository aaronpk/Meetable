<?php
namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Storage, Log;
use App\Events\WebmentionReceived;

class WebmentionReceivedListener implements ShouldQueue {

    public function handle(WebmentionReceived $event) {
        Log::info('Webmention received: '.($event->response->source_url ?: $event->response->url));

        $response = $event->response;

        $changed = false;

        // If there are any photos, attempt to download them and then rewrite the URLs
        if($response->photos) {
            $photos = [];
            foreach($response->photos as $photo) {
                $photos[] = $this->download($response, $photo);
            }
            $changed = true;
            $response->photos = $photos;
        }

        // If there is an author photo, download it and rewrite the URL
        if($response->author_photo) {
            $changed = true;
            $response->author_photo = $this->download($response, $response->author_photo);
        }

        $response->save();
    }

    private function download($response, $url) {
        Log::info('Downloading image '.$url);

        $filename = 'public/responses/'.$response->event->id.'/'.md5($url).'.jpg';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);

        Storage::put($filename, $temp);
        Storage::setVisibility($filename, 'public');

        $photo_url = Storage::url($filename);
        Log::info('  saved as '.$photo_url);

        return $photo_url;
    }

}
