<?php
namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Storage, Log;
use App\Events\ResizeImages;
use Image;
use App\Response, App\ResponsePhoto;

class ResizeImagesListener implements ShouldQueue {

    public function handle(ResizeImages $event) {
        $photo = $event->photo;

        Log::info('Resizing images for response '.$photo->response->link());

        if($photo->source_url) {
            // Download the original photo from the source URL
            $original_image = $this->download($photo);

            try {
                $image = Image::make($original_image);
                // Create resized versions
                $photo->createResizedImages($image);
            } catch(\Exception $e) {
                Log::error('Error resizing image '.$photo->source_url);
            }
        }
    }

    private function download($photo) {
        Log::info('Downloading image '.$photo->source_url);

        $filename = 'public/responses/'.$photo->response->event_id.'/'.md5($photo->source_url).'.jpg';

        $ch = curl_init($photo->source_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, \App\Helpers\HTTP::user_agent());
        $original_image = curl_exec($ch);

        if($original_image && curl_errno($ch) == 0) {
            Storage::put($filename, $original_image);
            Storage::setVisibility($filename, 'public');

            $photo_url = Storage::url($filename);
            Log::info('  saved as '.$photo_url);

            $photo->original_url = $photo_url;
            $photo->original_filename = $filename;
            $photo->save();

            return $original_image;
        } else {
            Log::error('  download failed: '.curl_error($ch));
        }

        return [null, null, null];
    }

}
