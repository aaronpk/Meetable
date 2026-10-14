<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\Rule;
use App\Event, App\EventDateOption, App\EventDateVote;
use Auth, Gate;

class EventDateVoteController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Records the logged-in user's answer to one of a proposed event's candidate dates.
     * An empty vote removes their answer. Returns the tallies for the whole poll so the
     * page can update the counts and the leading date in place.
     */
    public function vote(Event $event) {
        Gate::authorize('can-vote');

        // Voting closes once a date has been chosen
        abort_unless($event->is_proposed, 404);

        request()->validate([
            'option_id' => 'required|integer',
            'vote' => ['nullable', Rule::in(EventDateOption::$VOTES)],
        ]);

        $option = $event->date_options()->where('id', request('option_id'))->firstOrFail();

        $vote = EventDateVote::where('event_date_option_id', $option->id)
            ->where('user_id', Auth::user()->id)
            ->first();

        if(request('vote')) {
            if(!$vote) {
                $vote = new EventDateVote;
                $vote->event_date_option_id = $option->id;
                $vote->user_id = Auth::user()->id;
            }
            $vote->vote = request('vote');
            $vote->save();
        } elseif($vote) {
            $vote->delete();
        }

        return response()->json(self::poll_json($event, Auth::user()));
    }

    /**
     * The state of the poll as the page's JavaScript expects it: the counts and the
     * voters for each answer on every option, the user's own answers, and which
     * option is in the lead.
     */
    public static function poll_json(Event $event, $user) {
        $options = [];
        $user_votes = $user ? $event->date_votes_for_user($user) : [];

        foreach($event->date_options_with_tallies() as $option) {
            $voters = [];
            foreach(EventDateOption::$VOTES as $vote) {
                $voters[$vote] = $option->voters($vote)->map(function($voter){
                    return [
                        'name' => $voter->display_name(),
                        'photo' => $voter->photo ? \App\Helpers\Uri::safe_href($voter->photo) : null,
                        'url' => $voter->url ? \App\Helpers\Uri::safe_href($voter->url) : null,
                    ];
                })->values()->all();
            }

            $options[$option->id] = array_merge($option->vote_counts(), [
                'user_vote' => $user_votes[$option->id] ?? null,
                'voters' => $voters,
            ]);
        }

        $leading = $event->leading_date_option();

        return [
            'options' => $options,
            'leading_option_id' => $leading ? $leading->id : null,
        ];
    }

}
