<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Log, Storage;
use App\User, App\Event;
use Image;

class ResizeUserImages extends Command {

    protected $signature = 'migrate:resize_user_images';
    protected $description = 'Resize user avatars if they are the original large size';

    public function handle() {

        $users = User::all();
        foreach($users as $user) {

            if($user->photo) {
                $absolute_filename = Storage::path(str_replace('/storage/','public/',$user->photo));
                $filename = str_replace('/storage/','public/',$user->photo);
                $this->info('Processing '.$filename);

                try {
                    $image = Image::make($absolute_filename);
                    $this->info('  Dimensions: '.$image->width().'x'.$image->height());

                    if($image->width() > 150 || $image->height() > 150) {
                        $image->fit(150, 150);
                        $this->info('  New Dimensions: '.$image->width().'x'.$image->height());
                        $result = Storage::put($filename, $image->stream('jpg', 80));
                    }

                    #$user->save();
                } catch(\Intervention\Image\Exception\NotReadableException $e) {
                    $this->error('Error reading image');
                    $user->photo = null;
                    $user->save();
                }
            }

        }

    }

}
