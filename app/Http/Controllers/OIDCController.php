<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use DateTime, DateTimeZone, Exception;
use DB, Log;
use App\User;
use Illuminate\Support\Str;
use GuzzleHttp;

class OIDCController extends BaseController
{

    public static function oidcAuthURL() {
        $state = bin2hex(random_bytes(16));
        $code_verifier = bin2hex(random_bytes(50));

        session(['OIDC_STATE' => $state, 'OIDC_CODE_VERIFIER' => $code_verifier]);

        $code_challenge = self::pkce_challenge($code_verifier);

        $params = [
            'response_type' => 'code',
            'client_id' => env('OIDC_CLIENT_ID'),
            'redirect_uri' => route('oidc-redirect'),
            'state' => $state,
            'scope' => 'openid profile email',
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'S256',
        ];

        return env('OIDC_AUTHORIZATION_ENDPOINT') . '?' . http_build_query($params);
    }

    public function initiate() {
        return redirect(self::oidcAuthURL());
    }

    public function callback() {
        if(request('state') != session('OIDC_STATE')) {
            return view('auth/oidc-error', [
                'error' => 'Invalid OAuth State',
                'error_description' => 'There was a problem with the login process. Double check you are allowing cookies from this domain and try again.',
            ]);
        }

        if(!request('code')) {
            return view('auth/oidc-error', [
                'error' => 'OAuth Error',
                'error_description' => 'The OpenID Connect login process did not complete successfully. Please try again.',
                'details' => [
                    'error' => request('error'),
                    'error_description' => request('error_description')
                ]
            ]);
        }



        $guzzle = new GuzzleHttp\Client([
            'timeout' => 10,
        ]);
        // Exchange the authorization code now!
        try {
            $response = $guzzle->request('POST', env('OIDC_TOKEN_ENDPOINT'), [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $_GET['code'],
                    'redirect_uri' => route('oidc-redirect'),
                    'client_id' => env('OIDC_CLIENT_ID'),
                    'client_secret' => env('OIDC_CLIENT_SECRET'),
                    'code_verifier' => session('OIDC_CODE_VERIFIER'),
                ],
            ]);
        } catch(\GuzzleHttp\Exception\TransferException $e) {
            $details = null;
            Log::error($e->getMessage());
            if($e->hasResponse()) {
                $body = (string)$e->getResponse()->getBody();
                $details = json_decode($body, true);
                Log::error((string)$e->getResponse()->getBody());
            }
            return view('auth/oidc-error', [
                'error' => 'OAuth Error',
                'error_description' => 'The OpenID Connect server returned an error.',
                'details' => $details
            ]);
        }

        $body = (string)$response->getBody();

        $info = json_decode($body, true);

        if(!$info || !isset($info['id_token'])) {
            Log::info($info);
            return view('auth/oidc-error', [
                'error' => 'OAuth Error',
                'error_description' => 'The OpenID Connect server returned an invalid response.',
            ]);
        }

        $id_token = $info['id_token'];

        $claims_component = explode('.', $id_token)[1];
        $userinfo = json_decode(base64_decode($claims_component), true);


        // Check if this sub username is in the list of allowed users
        if(env('OIDC_ALLOWED_USERS')) {
            $allowedUsers = explode(' ', env('OIDC_ALLOWED_USERS'));
            if(!in_array($userinfo['sub'], $allowedUsers)) {
                Log::error('User '.$userinfo['sub'].' is not in the list of allowed users');
                return view('auth/oidc-error', [
                    'error' => 'User Not Allowed',
                    'error_description' => 'Sorry, you are not in the list of allowed users for this website.',
                ]);
            }
        }

        Log::info('User logged in: '.json_encode($userinfo));

        // Create the user record if it doesn't yet exist
        $user = User::where('identifier', $userinfo['sub'])->first();
        if(!$user) {
            $user = new User;
            $user->identifier = $userinfo['sub'];
            $user->email = $userinfo['email'] ?? '';
            $user->api_token = Str::random(80);
        }

        $user->name = $userinfo['name'] ?? '';

        if(env('OIDC_ADMIN_USERS')) {
            $adminUsers = explode(' ', env('OIDC_ADMIN_USERS'));
            if(in_array($userinfo['sub'], $adminUsers)) {
                $user->is_admin = true;
            }
        }

        $user->save();

        // Now set the session data to make this user logged-in
        session([
            'OIDC_USER' => $userinfo['sub'],
        ]);

        if(session('AUTH_RETURN_TO')) {
            return redirect(session('AUTH_RETURN_TO'));
        } else {
            return redirect('/');
        }
    }

    public function logout() {
        Auth::guard()->logout();
        return redirect('/');
    }

    protected static function pkce_challenge($verifier) {
        $sha256 = hash('sha256', $verifier, true);
        return rtrim(strtr(base64_encode($sha256), '+/', '-_'), '=');
    }

}
