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
    use CompletesOAuthLogin;

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
        if(!$this->validState('GITHUB_OAUTH_STATE')) {
            return view('auth/oauth-error', [
                'error' => __('login.errors.invalid_state'),
                'error_description' => __('login.errors.invalid_state_description'),
            ]);
        }

        if(!request('code')) {
            return view('auth/oauth-error', [
                'error' => __('login.errors.oauth_error'),
                'error_description' => __('login.errors.not_completed', ['provider' => 'GitHub']),
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
                'error' => __('login.errors.oauth_error'),
                'error_description' => __('login.errors.no_access_token', ['provider' => 'GitHub']),
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
                'error' => __('login.errors.oauth_error'),
                'error_description' => __('login.errors.no_user_info', ['provider' => 'GitHub']),
            ]);
        }

        // Check if this GitHub username is in the list of allowed users
        if(env('GITHUB_ALLOWED_USERS')) {
            $allowedUsers = explode(' ', env('GITHUB_ALLOWED_USERS'));
            if(!in_array($userdata['login'], $allowedUsers)) {
                return view('auth/oauth-error', [
                    'error' => __('login.errors.not_allowed'),
                    'error_description' => __('login.errors.not_in_allowed_users'),
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

        // Now make this user logged-in
        return $this->redirectAfterLogin('GITHUB_USER', $userdata['html_url']);
    }

}
