<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Log, Storage;
use Image;

class ResponsePhoto extends Model
{

    protected $hidden = [
        'id', 'event_id', 'response_id',
    ];

    public static function create(Response $response, $data) {
        $max = self::where('event_id', $response->event->id)->max('sort_order');

        $photo = new ResponsePhoto;
        $photo->response_id = $response->id;
        $photo->event_id = $response->event_id;
        $photo->sort_order = $max + 1;
        foreach($data as $k=>$v)
            $photo->{$k} = $v;
        $photo->save();

        return $photo;
    }

    public function event() {
        return $this->belongsTo('\App\Event');
    }

    public function response() {
        return $this->belongsTo('\App\Response');
    }

    public function createResizedImages($image) {
        Log::info('Creating resized images for '.$this->original_url);

        $sizes = [
            ['w'=>1600,'h'=>null,'name'=>'full_url'],
            ['w'=>710,'h'=>null,'name'=>'large_url'],
            ['w'=>230,'h'=>230,'name'=>'square_url'],
        ];
        foreach($sizes as $size) {
            $copy = $image;

            if($size['w'] && $size['h']) {
                $copy->fit($size['w'], $size['h']);
            } else {
                $copy->resize($size['w'], $size['h'], function($constraint){
                    $constraint->aspectRatio();
                    $constraint->upsize(); // prevent making it bigger than the original
                });
            }

            Log::info('Resizing to '.$size['w'].'x'.$size['h']);

            $basename = basename($this->original_url);
            $filename = 'public/responses/'.$this->event_id.'/'.$size['w'].'x'.$size['h'].'-'.$basename;

            Log::info('Saving resized image as '.$filename);

            Storage::put($filename, $copy->stream('jpg', 80));
            Storage::setVisibility($filename, 'public');

            $this->{$size['name']} = Storage::url($filename);
            Log::info('Saving resized URL '.$this->{$size['name']});
        }

        $this->save();
    }

}
