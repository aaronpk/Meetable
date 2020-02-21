<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\EventRevision, App\Tag, App\Response, App\ResponsePhoto;
use Illuminate\Support\Str;
use Auth, Storage, Gate, Log;
use Image;

class ResponseController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function edit_responses(Event $event) {
        Gate::authorize('manage-event', $event);

        $responses = $event->responses()->get();

        return view('edit-responses', [
            'event' => $event,
            'responses' => $responses,
        ]);
    }

    public function moderate_all_responses() {
        Gate::authorize('create-event');

        $responses = Response::where('approved', 0)->get();

        return view('moderate-responses', [
            'responses' => $responses,
        ]);
    }

    public function moderate_responses(Event $event) {
        Gate::authorize('manage-event', $event);

        $responses = $event->pending_responses()->get();

        return view('moderate-responses', [
            'event' => $event,
            'responses' => $responses,
        ]);
    }

    public function get_response_details(Event $event, Response $response) {
        Gate::authorize('manage-event', $event);
        $response->photos; // load photos so they are part of the response
        return response()->json($response);
    }

    public function delete_response(Event $event, Response $response) {
        Gate::authorize('manage-event', $event);

        $id = $response->id;
        $response->delete();

        ResponsePhoto::where('response_id', $id)->delete();

        return response()->json([
            'result' => 'ok',
            'response_id' => $id,
        ]);
    }

    public function approve_response(Event $event, Response $response) {
        Gate::authorize('manage-event', $event);

        $response->approved = true;
        $response->approved_by = Auth::user()->id;
        $response->approved_at = date('Y-m-d H:i:s');
        $response->save();

        return response()->json([
            'result' => 'ok',
            'response_id' => $response->id,
        ]);
    }

}
