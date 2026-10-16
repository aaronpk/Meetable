<?php
namespace App\Http\Controllers\Auth;

use App\Helpers\Uri;
use App\User;
use App\Jobs\DownloadProfilePhoto;
use Illuminate\Support\Str;

trait CompletesOAuthLogin {

    // Checks the state returned by the provider against the one stored when the login
    // started. The stored state can only be used once.
    protected function validState($session_key) {
        $expected = session($session_key);
        session()->forget($session_key);

        $state = request('state');

        return is_string($expected) && $expected !== ''
            && is_string($state)
            && hash_equals($expected, $state);
    }

    // Finds the user for this provider identifier, creating it on first login, stores the
    // profile fields the provider sent so they stay current, and queues a download of the
    // photo when there is one. $defaults are only set when the user is first created.
    protected function syncUser($identifier, array $fields, $photo_url = null, array $defaults = []) {
        $user = User::where('identifier', $identifier)->first();
        if(!$user) {
            $user = new User;
            $user->identifier = $identifier;
            $user->api_token = Str::random(80);
            foreach($defaults as $field => $value)
                $user->{$field} = $value;
        }

        foreach($fields as $field => $value)
            $user->{$field} = $value;

        $user->save();

        if($photo_url)
            DownloadProfilePhoto::dispatch($user, $photo_url);

        return $user;
    }

    // Starts a new session for the user who just logged in and sends them back to the
    // page they came from, as long as it's on this website
    protected function redirectAfterLogin($session_key, $user_id) {
        $return_to = Uri::same_origin_path(session('AUTH_RETURN_TO'));

        session()->forget('AUTH_RETURN_TO');
        session()->regenerate();
        session([$session_key => $user_id]);

        return redirect($return_to);
    }

}
