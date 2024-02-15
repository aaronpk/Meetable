<?php
namespace App\Helpers;

use Log;
use GuzzleHttp\Client;
use App\Setting;

class Notification {

    public static function send($text) {
        if(Setting::value('notification_endpoint')) {
            Log::info($text);
            $client = new Client();
            $response = $client->request('POST', Setting::value('notification_endpoint'), [
                'form_params' => [
                    'h' => 'entry', // make it a micropub request
                    'content' => $text,
                ],
                'headers' => [
                    'Authorization' => 'Bearer '.Setting::value('notification_token'),
                ]
            ]);
            Log::info('Notification response: '.$response->getStatusCode().' '.$response->getBody());
        }
    }

}
