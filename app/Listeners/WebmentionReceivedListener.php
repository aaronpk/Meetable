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
        // Already downloaded
        if(\p3k\url\host_matches($url, env('APP_URL')))
            return $url;

        $filename = 'responses/'.$response->event->id.'/'.md5($url).'.jpg';
        $full_filename = __DIR__.'/../../storage/app/public/'.$filename;

        $dir = dirname($full_filename);
        if(!file_exists($dir))
            mkdir($dir, 0755, true);

        $fp = fopen($full_filename, 'w+');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        return 'public/'.$filename;
    }

}
