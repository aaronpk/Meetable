<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\Response, App\EventRevision, App\Tag;
use Illuminate\Support\Str;
use Auth;


class EventResponseController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function save_rsvp(Event $event) {

        # Load existing RSVP for this user if there is one already
        $rsvp = $event->rsvp_for_user(Auth::user());

        if(!$rsvp) {
            $rsvp = new Response;
            $rsvp->event_id = $event->id;
            $rsvp->rsvp_user_id = Auth::user()->id;
            $rsvp->created_by = Auth::user()->id;
            $rsvp->approved_by = Auth::user()->id;
            $rsvp->approved_at = date('Y-m-d H:i:s');
            $rsvp->approved = true;
        }

        $rsvp->rsvp = request('rsvp') ? 'yes' : 'no';
        $rsvp->save();

        return response()->json([
            'redirect' => $event->permalink()
        ]);
    }

    public function delete_rsvp(Event $event) {

        # Load existing RSVP for this user if there is one already
        $rsvp = $event->rsvp_for_user(Auth::user());

        if($rsvp) {
            $rsvp->delete();
        }

        return response()->json([
            'redirect' => $event->permalink()
        ]);
    }

}
