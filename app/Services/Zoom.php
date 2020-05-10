<?php
namespace App\Services;

use App\Setting;
use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class Zoom {

    public static function schedule_meeting(&$event) {
        // Note: the $event may not have been saved in the database yet

        $time = time();
        $signer = new Sha256();
        $token = (new JWT\Builder())->issuedBy(Setting::value('zoom_api_key')) // iss
                                ->permittedFor(null) // aud
                                ->issuedAt($time) // Configures the time that the token was issue (iat claim)
                                ->expiresAt($time + 3600) // Configures the expiration time of the token (exp claim)
                                ->getToken($signer, new Key(Setting::value('zoom_api_secret'))); // Signs the token
        $zoom_access_token = (string)$token;

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
            "authorization: Bearer ".$zoom_access_token,
            "content-type: application/json"
          ],
        ]);
        $response = curl_exec($ch);
        $meeting = json_decode($response, true);

        return $meeting['join_url'] ?? null;
    }


}
