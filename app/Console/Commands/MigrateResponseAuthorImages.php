<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Log, Storage;
use App\User, App\Event, App\Response;
use Image;

class MigrateUserImages extends Command {

    protected $signature = 'migrate:response_author_images';
    protected $description = 'Migrate response author images to new URLs';

    public function handle() {

        $responses = Response::withTrashed()->get();
        foreach($responses as $response) {

            if($response->author_photo && preg_match('/^public\//', $response->author_photo)) {
                $this->info('Processing '.$response->author_photo);

                $image = Image::make(storage_path('app/'.$response->author_photo));
                $image->fit(150, 150);

                Storage::put($response->author_photo, $image->stream('jpg', 80));
                Storage::setVisibility($response->author_photo, 'public');

                $response->author_photo = Storage::url($response->author_photo);
                $response->save();
            }

        }

    }

}
