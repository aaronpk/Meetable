<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use DateTime, DateTimeZone, Exception;
use Auth, DB, Log;
use App\User;
use Illuminate\Support\Str;
use GuzzleHttp;

class OIDCController extends BaseController
{
    use CompletesOAuthLogin;

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
        if(!$this->validState('OIDC_STATE')) {
            return view('auth/oidc-error', [
                'error' => __('login.errors.invalid_state'),
                'error_description' => __('login.errors.invalid_state_description'),
            ]);
        }

        if(!request('code')) {
            return view('auth/oidc-error', [
                'error' => __('login.errors.oauth_error'),
                'error_description' => __('login.errors.not_completed', ['provider' => 'OpenID Connect']),
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
                    'code_verifier' => session()->pull('OIDC_CODE_VERIFIER'),
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
                'error' => __('login.errors.oauth_error'),
                'error_description' => __('login.errors.oidc_server_error'),
                'details' => $details
            ]);
        }

        $body = (string)$response->getBody();

        $info = json_decode($body, true);

        if(!$info || !isset($info['id_token'])) {
            Log::info('The OpenID Connect token response had no id_token');
            return view('auth/oidc-error', [
                'error' => __('login.errors.oauth_error'),
                'error_description' => __('login.errors.oidc_invalid_response'),
            ]);
        }

        $id_token = $info['id_token'];

        // The ID token came straight from the token endpoint over TLS, so its claims are
        // read without checking the signature. JWTs use unpadded base64url encoding.
        $claims_component = explode('.', $id_token)[1] ?? '';
        $userinfo = json_decode(base64_decode(strtr($claims_component, '-_', '+/')), true);

        if(!is_array($userinfo) || empty($userinfo['sub']) || !is_string($userinfo['sub'])) {
            return view('auth/oidc-error', [
                'error' => __('login.errors.oauth_error'),
                'error_description' => __('login.errors.oidc_no_subject'),
            ]);
        }


        // Check if this sub username is in the list of allowed users
        if(env('OIDC_ALLOWED_USERS')) {
            $allowedUsers = explode(' ', env('OIDC_ALLOWED_USERS'));
            if(!in_array($userinfo['sub'], $allowedUsers)) {
                Log::error('User '.$userinfo['sub'].' is not in the list of allowed users');
                return view('auth/oidc-error', [
                    'error' => __('login.errors.not_allowed'),
                    'error_description' => __('login.errors.not_in_allowed_users'),
                ]);
            }
        }

        Log::info('User logged in: '.$userinfo['sub']);

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

        // Now make this user logged-in
        return $this->redirectAfterLogin('OIDC_USER', $userinfo['sub']);
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
