<?php
namespace App\Services;

use App\Setting;
use DateTimeImmutable;

class Zoom {

    public static function schedule_meeting(&$event) {
        // Note: the $event may not have been saved in the database yet

        $token = self::get_access_token();

        $meeting = [
            'topic' => $event->name,
            'type' => 2, // scheduled meeting
            'start_time' => $event->start_datetime_local('Y-m-d\TH:i:s'),
            'timezone' => $event->timezone,
            'duration' => $event->duration_minutes(),
            'schedule_for' => Setting::value('zoom_email'),
            'settings' => [
                'use_pmi' => false,
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
          CURLOPT_URL => "https://api.zoom.us/v2/users/".Setting::value('zoom_email')."/meetings",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POSTFIELDS => json_encode($meeting),
          CURLOPT_HTTPHEADER => [
            "authorization: Bearer ".$token['access_token'],
            "content-type: application/json"
          ],
        ]);
        $response = curl_exec($ch);
        $zoom_meeting = json_decode($response, true);

        return $zoom_meeting ?? null;
    }

    public static function update_meeting(&$event) {
        $token = self::get_access_token();

        $meeting = [
            'topic' => $event->name,
            'start_time' => $event->start_datetime_local('Y-m-d\TH:i:s'),
            'timezone' => $event->timezone,
            'duration' => $event->duration_minutes(),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
          CURLOPT_URL => "https://api.zoom.us/v2/meetings/".$event->zoom_meeting_id,
          CURLOPT_CUSTOMREQUEST => 'PATCH',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POSTFIELDS => json_encode($meeting),
          CURLOPT_HTTPHEADER => [
            "authorization: Bearer ".$token['access_token'],
            "content-type: application/json"
          ],
        ]);
        $response = curl_exec($ch);
        $zoom_meeting = json_decode($response, true);

    }

    private static function get_access_token() {
        $ch = curl_init();
        curl_setopt_array($ch, [
          CURLOPT_URL => "https://zoom.us/oauth/token",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'account_credentials',
            'account_id' => Setting::value('zoom_account_id'),
          ]),
          CURLOPT_USERPWD => Setting::value('zoom_client_id').':'.Setting::value('zoom_client_secret'),
        ]);
        $response = curl_exec($ch);
        $token = json_decode($response, true);
        return $token;
    }

}
