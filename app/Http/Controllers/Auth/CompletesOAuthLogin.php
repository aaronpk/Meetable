<?php
namespace App\Http\Controllers\Auth;

use App\Helpers\Uri;

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
