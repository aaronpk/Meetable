<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use DateTime, DateTimeZone, Exception;
use DB;
use App\User;
use Illuminate\Support\Str;

class GitHubController extends BaseController
{

    public static function githubAuthURL() {
        $state = bin2hex(random_bytes(16));

        session(['GITHUB_OAUTH_STATE' => $state]);

        $params = [
            'response_type' => 'code',
            'client_id' => env('GITHUB_CLIENT_ID'),
            'redirect_uri' => route('github-oauth-redirect'),
            'state' => $state,
        ];

        return 'https://github.com/login/oauth/authorize?' . http_build_query($params);
    }

    public function callback() {
        if(request('state') != session('GITHUB_OAUTH_STATE')) {
            return view('auth/oauth-error', [
                'error' => 'Invalid OAuth State',
                'error_description' => 'There was a problem with the login process. Double check you are allowing cookies from this domain and try again.',
            ]);
        }

        if(!request('code')) {
            return view('auth/oauth-error', [
                'error' => 'OAuth Error',
                'error_description' => 'The GitHub login process did not complete successfully. Please try again.',
            ]);
        }

        $ch = curl_init('https://github.com/login/oauth/access_token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.github.v3+json, application/json',
            'User-Agent: '.env('APP_URL'),
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'code' => request('code'),
            'redirect_uri' => route('github-oauth-redirect'),
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
        ]));
        $response = curl_exec($ch);
        $data = json_decode($response, true);

        if(!isset($data['access_token'])) {
            return view('auth/oauth-error', [
                'error' => 'OAuth Error',
                'error_description' => 'Unable to get an access token from GitHub. Please try again.',
            ]);
        }

        $access_token = $data['access_token'];

        // Look up user info with this access token
        $ch = curl_init('https://api.github.com/user');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.github.v3+json, application/json',
            'User-Agent: '.env('APP_URL'),
            'Authorization: Token '.$access_token,
        ]);
        $response = curl_exec($ch);
        $userdata = json_decode($response, true);

        if(!isset($userdata['id'])) {
            return view('auth/oauth-error', [
                'error' => 'OAuth Error',
                'error_description' => 'Unable to get user info from GitHub. Please try again.',
            ]);
        }

        // Check if this GitHub username is in the list of allowed users
        if(env('GITHUB_ALLOWED_USERS')) {
            $allowedUsers = explode(' ', env('GITHUB_ALLOWED_USERS'));
            if(!in_array($userdata['login'], $allowedUsers)) {
                return view('auth/oauth-error', [
                    'error' => 'User Not Allowed',
                    'error_description' => 'Sorry, you are not in the list of allowed users for this website.',
                ]);
            }
        }

        // Create the user record if it doesn't yet exist
        $user = User::where('identifier', $userdata['html_url'])->first();
        if(!$user) {
            $user = new User;
            $user->identifier = $userdata['html_url'];
            $user->url = $userdata['html_url'];
            $user->photo = $user->downloadProfilePhoto($userdata['avatar_url']);
            $user->api_token = Str::random(80);
        }

        $user->name = $userdata['name'];

        if(env('GITHUB_ADMIN_USERS')) {
            $adminUsers = explode(' ', env('GITHUB_ADMIN_USERS'));
            if(in_array($userdata['login'], $adminUsers)) {
                $user->is_admin = true;
            }
        }

        $user->save();

        // Now set the session data to make this user logged-in
        session([
            'GITHUB_USER' => $userdata['html_url'],
        ]);

        if(session('AUTH_RETURN_TO')) {
            return redirect(session('AUTH_RETURN_TO'));
        } else {
            return redirect('/');
        }
    }

}
