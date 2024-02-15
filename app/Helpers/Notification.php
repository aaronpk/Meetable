<?php
namespace App\Helpers;

use Log;
use GuzzleHttp\Client;
use App\Setting;

class Notification {

    public static function send($text, $channel) {
        if(Setting::value('notification_endpoint')) {
            Log::info($text);
            $client = new Client();
            $response = $client->request('POST', Setting::value('notification_endpoint'), [
                'form_params' => [
                    'h' => 'entry',
                    'content' => $text,
                    'channel' => $channel,
                ],
                'headers' => [
                    'Authorization' => 'Bearer '.Setting::value('notification_token'),
                ]
            ]);
            Log::info('Notification response: '.$response->getStatusCode().' '.$response->getBody());
        }
    }

    public static function sendPrimary($text) {
        $channel = Setting::value('notification_channel_primary');
        self::send($text, $channel);
    }

    public static function sendMeta($text) {
        $channel = Setting::value('notification_channel_meta');
        self::send($text, $channel);
    }

}
