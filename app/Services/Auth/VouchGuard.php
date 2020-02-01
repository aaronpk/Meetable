<?php
namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\User;
use Illuminate\Support\Str;
use App\Events\UserCreated;

class VouchGuard extends CustomGuard {

    protected $request;
    protected $provider;
    protected $user;

    public function redirectWhenNotAuthenticated($fromUrl) {
        return 'https://'.env('VOUCH_HOSTNAME').'/login?url='.urlencode($fromUrl);
    }

    public function login_url() {
        $url = session('AUTH_RETURN_TO') ?: route('index');
        return 'https://'.env('VOUCH_HOSTNAME').'/login?url='.urlencode($url);
    }

    public function logout() {
        return 'https://'.env('VOUCH_HOSTNAME').'/logout?url='.urlencode(route('index'));
    }

    public function __construct(UserProvider $provider, Request $request) {
        $this->request = $request;
        $this->provider = $provider;
        $this->user = null;
    }

    public function check() {
        $url = $this->request->server->get('HTTP_REMOTE_USER');
        return $url == true;
    }

    public function guest() {
        $url = $this->request->server->get('HTTP_REMOTE_USER');
        return $url != true;
    }

    public function user() {
        static $cached = false;

        $username = $this->request->server->get('HTTP_REMOTE_USER');

        if(!$username)
          return null;

        if($cached && $cached->identifier == $username)
            return $cached;

        list($user, $created) = $this->getUserFromUsername($username);

        if($created)
            event(new UserCreated($user));

        $cached = $user;
        return $user;
    }

    protected function getUserFromUsername($username) {
        $user = User::where('identifier', $username)->first();

        $created = false;

        if(!$user) {
            $user = new User();
            $user->identifier = $username;
            // If the username is a URL, add it as the URL field
            if(\p3k\url\is_url($username))
                $user->url = $username;
            $user->api_token = Str::random(80);
            $user->save();
            $created = true;
        }

        return [$user, $created];
    }

    public function id() {
        $user = $this->user();
        return $user ? $user->id : null;
    }

    public function validate(array $credentials = []) {
        return false;
    }

    public function setUser(Authenticatable $user) {
        return false;
    }

}
