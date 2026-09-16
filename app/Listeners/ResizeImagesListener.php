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

            if(!$original_image)
                return;

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

        // Only store the file once it's known to be an image
        $original_image = \App\Helpers\SafeHTTP::fetch_image($photo->source_url);

        if(!$original_image) {
            Log::error('  download failed');
            return null;
        }

        $filename = 'public/responses/'.$photo->response->event_id.'/'.md5($photo->source_url).'.jpg';

        Storage::put($filename, $original_image);
        Storage::setVisibility($filename, 'public');

        $photo_url = Storage::url($filename);
        Log::info('  saved as '.$photo_url);

        $photo->original_url = $photo_url;
        $photo->original_filename = $filename;
        $photo->save();

        return $original_image;
    }

}
