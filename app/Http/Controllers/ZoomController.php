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
    // How old a signed webhook can be before it's rejected, so captured requests can't be replayed
    const MAX_AGE_SECONDS = 300;

    public function webhook(Request $request) {

        Log::info('Zoom webhook: '.request('event'));

        if(!Setting::value('zoom_webhook_secret')) {
            return response()->json([
                'error' => 'not_configured',
            ], 404);
        }

        if(request('event') == 'endpoint.url_validation') {
            $plain_token = request('payload.plainToken');

            // Only sign tokens shaped like the ones Zoom sends. Otherwise this would sign any
            // text, including the "v0:timestamp:body" string used to verify webhooks.
            if(!is_string($plain_token) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $plain_token)) {
                return response()->json([
                    'error' => 'invalid_request',
                ], 400);
            }

            $encrypted_token = hash_hmac('sha256', $plain_token, Setting::value('zoom_webhook_secret'));

            return response()->json([
                'plainToken' => $plain_token,
                'encryptedToken' => $encrypted_token,
            ]);
        }

        if(!$this->verify_webhook($request)) {
            Log::debug('Received invalid payload to webhook URL');
            return response()->json([
                'error' => 'unauthorized',
            ], 401);
        }

        if(!request('payload.object.id')) {
            return response()->json([
                'result' => 'ok',
            ]);
        }

        $event = Event::where('zoom_meeting_id', request('payload.object.id'))->first();

        if(!$event) {
            Log::info('Could not find event for Zoom meeting: ' . request('payload.object.id'));
            return response()->json([
                'result' => 'ok',
            ]);
        }

        switch(request('event')) {

            case 'meeting.started':
            case 'meeting.ended':

                $status = request('event') == 'meeting.started' ? 'started' : 'ended';
                $event->zoom_meeting_status = $status;
                $event->save();

                // Send notification to configured chat
                switch(request('event')) {
                    // case 'meeting.started':
                    //     Notification::sendMeta('"' . $event->name . '" call started, join now: '.$event->absolute_permalink());
                    //     break;
                    case 'meeting.ended':
                        Notification::sendMeta('"' . $event->name . '" call ended '.$event->absolute_permalink());
                        break;
                }

                break;

            case 'meeting.participant_joined':

                $event->current_participants += 1;
                $event->max_participants = max($event->max_participants, $event->current_participants);
                $event->save();

                break;

            case 'meeting.participant_left':

                $event->current_participants -= 1;
                $event->save();

                break;

        }

        return response()->json([
            'result' => 'ok',
        ]);
    }

    private function verify_webhook(Request $request) {
        $timestamp = $request->header('x-zm-request-timestamp');
        $signature = $request->header('x-zm-signature');

        if(!is_numeric($timestamp) || !is_string($signature))
            return false;

        if(abs(time() - (int)$timestamp) > self::MAX_AGE_SECONDS)
            return false;

        $base = 'v0:'.$timestamp.':'.$request->getContent();
        $hash = hash_hmac('sha256', $base, Setting::value('zoom_webhook_secret'));
        return hash_equals('v0='.$hash, $signature);
    }
}

