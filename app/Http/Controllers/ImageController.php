<?php
namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Storage;
use Image;

class ImageController extends BaseController
{

    public function render($settings, $url) {
        // Settings are similar to https://github.com/willnorris/imageproxy#url-structure
        // but a limited subset of them are supported, only the ones used by this project:
        //   WxH,sc

        $input = explode(',', $settings);
        $opts['w'] = false;
        $opts['h'] = false;
        $opts['sc'] = false;
        $opts['sig'] = false;
        foreach($input as $i) {
            if(preg_match('/(\d+)x(\d+)/', $i, $match)) {
                $opts['w'] = $match[1];
                $opts['h'] = $match[2];
            } elseif($i == 'sc') {
                $opts['sc'] = true;
            } elseif(preg_match('/^s(.+)/', $i, $match)) {
                $opts['sig'] = $match[1];
                $settings = str_replace(','.$match[0], '', $settings);
            }
        }

        // Validate the signature
        if(!$opts['sig'])
            return $this->invalid();

        $urlToSign = $url.'#'.$settings;

        $expected = strtr(base64_encode(hash_hmac('sha256', $urlToSign, env('APP_KEY'), 1)), '/+' , '_-');

        if($opts['sig'] != $expected)
            return $this->invalid('invalid signature');

        // Check for a cached image already
        $hash = md5($urlToSign);
        $cacheFile = 'cache/'.substr($hash,0,1).'/'.substr($hash,1,1).'/'.$hash.'.jpg';

        if(Storage::exists($cacheFile)) {
            return response()->file(storage_path('app/'.$cacheFile));
        }

        if(parse_url($url, PHP_URL_HOST)) {
            $image = Image::make($url); // allow resizing external images
        } else {
            if(Storage::exists($url))
                $image = Image::make(storage_path('app/'.$url));
            else
                return $this->invalid('not found');
        }

        // Resize the image
        if($opts['w'] == 0 || $opts['h'] == 0) {
            $image->resize($opts['w'] ?: null, $opts['h'] ?: null, function($constraint){
                $constraint->upsize(); // prevent upsizing
                $constraint->aspectRatio(); // maintain aspect ratio
            });
        } else {
            $image->fit($opts['w'], $opts['h'], function($constraint){
                $constraint->upsize(); // prevent upsizing
            });
        }

        // Store a cached copy
        Storage::put($cacheFile, $image->stream('jpg', 80));

        return $image->response();
    }

    private function invalid($msg=false) {
        return response()->json([
            'error' => ($msg ?: 'invalid request')
        ], 400);
    }

}
