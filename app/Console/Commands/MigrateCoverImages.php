<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Log, Storage;
use App\User, App\Event;
use Image;

class MigrateCoverImages extends Command {

    protected $signature = 'migrate:cover_images';
    protected $description = 'Migrate cover photos to the new storage format';

    public function handle() {

        $events = Event::all();
        foreach($events as $event) {

            if($event->cover_image) {
                $this->info('Processing '.$event->cover_image);
                $original_filename = $event->cover_image;

                if(!Storage::exists($original_filename)) {
                    $this->error('  file not found');
                    continue;
                }

                // Load the original uploaded image
                $image = Image::make(Storage::get($original_filename));

                // Resize
                $this->info('  resizing...');
                $image->fit(1440, 640);

                // Save in the new location
                $filename = 'public/events/'.date('Ymd', strtotime($event->updated_at)).'-'.Str::random(30).'.jpg';

                // Store the resized image at the new location
                $this->info('  storing as '.$filename);
                Storage::put($filename, $image->stream('jpg', 80), 'public');

                // Save the URL of the new file
                $event->cover_image = Storage::url($filename);
                $event->save();

                // Remove the old file
                $this->info('  deleting old file');
                Storage::delete($original_filename);
            }

        }

    }

}
