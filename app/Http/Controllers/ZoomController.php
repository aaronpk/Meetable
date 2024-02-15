<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use DateTime, DateTimeZone, Exception;
use DB, Log;
use App\Event, App\Setting;
use App\Helpers\Notification;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ZoomController extends BaseController
{
    public function webhook(Request $request) {

        Log::info(request());

        switch(request('event')) {
            case 'endpoint.url_validation':

                $plain_token = request('payload.plainToken');
                $encrypted_token = hash_hmac('sha256', $plain_token, Setting::value('zoom_webhook_secret'));

                return response()->json([
                    'plainToken' => $plain_token,
                    'encryptedToken' => $encrypted_token,
                ]);

            case 'meeting.started':
            case 'meeting.ended':

                if(!$this->verify_webhook($request)) {
                    Log::debug('Received invalid payload to webhook URL');
                    return response()->json([
                        'error' => 'unauthorized',
                    ]);
                }
                $event = Event::where('zoom_meeting_id', request('payload.object.id'))->first();

                if($event) {
                    $status = request('event') == 'meeting.started' ? 'started' : 'ended';
                    $event->zoom_meeting_status = $status;
                    $event->save();

                    // Send notification to configured chat
                    switch(request('event')) {
                        case 'started':
                            Notification::send('"' . $event->name . '" call started, join now: '.$event->meeting_url);
                            break;
                        case 'ended':
                            Notification::send('"' . $event->name . '" call ended');
                            break;
                    }
                }

                return response()->json([
                    'result' => 'ok',
                ]);

        }
    }

    private function verify_webhook(Request $request) {
        $base = 'v0:'.$request->header('x-zm-request-timestamp').':'.$request->getContent();
        $hash = hash_hmac('sha256', $base, Setting::value('zoom_webhook_secret'));
        $sig = 'v0='.$hash;
        return $sig == $request->header('x-zm-signature');
    }
}

