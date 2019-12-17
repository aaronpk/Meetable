<?php
namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\User;

class VouchGuard implements Guard {

    protected $request;
    protected $provider;
    protected $user;

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

        $url = $this->request->server->get('HTTP_REMOTE_USER');

        if(!$url)
          return null;

        if($cached && $cached->url == $url)
            return $cached;

        $user = User::where('url', $url)->first();
        if(!$user) {
            $user = new User();
            $user->url = $url;
            $user->api_token = Str::random(80);
            $user->save();
        }
        $cached = $user;
        return $user;
    }

    public function id() {
        $user = $this->user();
        return $user->id;
    }

    public function validate(array $credentials = []) {
        return false;
    }

    public function setUser(Authenticatable $user) {
        return false;
    }

}
