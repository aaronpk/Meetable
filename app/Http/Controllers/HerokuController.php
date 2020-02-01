<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use DateTime, DateTimeZone, Exception;
use DB;
use App\User;
use Illuminate\Support\Str;

class HerokuController extends BaseController
{

    public static function authURL() {
        $state = bin2hex(random_bytes(16));

        session(['HEROKU_OAUTH_STATE' => $state]);

        $params = [
            'response_type' => 'code',
            'client_id' => env('HEROKU_CLIENT_ID'),
            'state' => $state,
            'scope' => 'identity',
        ];

        return 'https://id.heroku.com/oauth/authorize?' . http_build_query($params);
    }

    public function callback() {
        if(request('state') != session('HEROKU_OAUTH_STATE')) {
            return view('auth/heroku-error', [
                'error' => 'Invalid OAuth State',
                'error_description' => 'There was a problem with the login process. Double check you are allowing cookies from this domain and try again.',
            ]);
        }

        if(!request('code')) {
            return view('auth/heroku-error', [
                'error' => 'OAuth Error',
                'error_description' => 'The Heroku login process did not complete successfully. Please try again.',
            ]);
        }

        $ch = curl_init('https://id.heroku.com/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.heroku+json; version=3',
            'User-Agent: '.env('APP_URL'),
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'code' => request('code'),
            'client_id' => env('HEROKU_CLIENT_ID'),
            'client_secret' => env('HEROKU_CLIENT_SECRET'),
        ]));
        $response = curl_exec($ch);
        $data = json_decode($response, true);

        if(!isset($data['access_token'])) {
            return view('auth/heroku-error', [
                'error' => 'OAuth Error',
                'error_description' => 'Unable to get an access token from Heroku. Please try again.',
            ]);
        }

        $access_token = $data['access_token'];

        // Look up user info with this access token
        $ch = curl_init('https://api.heroku.com/account');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.heroku+json; version=3',
            'User-Agent: '.env('APP_URL'),
            'Authorization: Bearer '.$access_token,
        ]);
        $response = curl_exec($ch);
        $userdata = json_decode($response, true);

        if(!isset($userdata['id'])) {
            return view('auth/github-error', [
                'error' => 'OAuth Error',
                'error_description' => 'Unable to get user info from Heroku. Please try again.',
            ]);
        }

        $gravatar_url = 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($userdata['email']))).'?s=120';

        // The Heroku login is not meant for general usage, only for the one person who created it to log in.
        // If there are no users in the DB, then add this user as an admin and let them through.
        // If there are users, check that this user already exists, and reject anyone else.
        // That will let people manually add other Heroku user IDs if they really want, but by
        // default will only let the one user who set up the app log in.
        if(User::count() == 0) {
            $user = new User;
            $user->identifier = $userdata['id'];
            $user->name = $userdata['name'];
            $user->email = $userdata['email'];
            $user->photo = $gravatar_url;
            $user->api_token = Str::random(80);
            $user->is_admin = 1;
            $user->save();
        } else {
            // Check if this user already exists
            $user = User::where('identifier', $userdata['id'])->first();
            if(!$user) {
                return view('auth/heroku-error', [
                    'error' => 'User Not Allowed',
                    'error_description' => 'Sorry, you are not in the list of allowed users for this website.',
                ]);
            }
            $user->name = $userdata['name'];
            $user->email = $userdata['email'];
            $user->photo = $gravatar_url;
            $user->save();
        }


        // Now set the session data to make this user logged-in
        session([
            'HEROKU_USERID' => $userdata['id'],
        ]);

        if(session('AUTH_RETURN_TO')) {
            return redirect(session('AUTH_RETURN_TO'));
        } else {
            return redirect('/');
        }
    }

}
