<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Log, Storage;
use App\User, App\Event, App\Response, App\ResponsePhoto;
use Image;

class MigrateResponsePhotos extends Command {

    protected $signature = 'migrate:response_photos';
    protected $description = 'Migrate response photos and create resized versions';

    public function handle() {

        $responses = Response::withTrashed()->get();
        foreach($responses as $response) {

            $photos = ResponsePhoto::where('response_id', $response->id)->get();
            foreach($photos as $photo) {

                if(preg_match('/^\/storage\/responses\//', $photo->original_url))
                    $photo->original_url = str_replace('/storage/', 'public/', $photo->original_url);

                $filename = $photo->original_url;

                $this->info('Processing '.$photo->original_url);
                try {
                    $image = Image::make(storage_path('app/'.$photo->original_url));
                    $photo->createResizedImages($image);
                    $photo->original_url = Storage::url($photo->original_url);
                    $photo->original_filename = $filename;
                    $photo->save();
                } catch(\Exception $e) {
                    Log::error('Could not read file: '.$photo->original_url);
                }
            }

        }

    }

}
