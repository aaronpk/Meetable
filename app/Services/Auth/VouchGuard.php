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
        $url = $this->request->server->get('HTTP_REMOTE_USER');
        $user = User::where('url', $url)->first();
        if(!$user) {
            $user = new User();
            $user->url = $url;
            $user->save();
        }
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
