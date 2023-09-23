<?php
namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\User;
use Illuminate\Support\Str;
use App\Events\UserCreated;
use App\Http\Controllers\Auth\HerokuController;

class HerokuGuard extends CustomGuard {

    protected $request;
    protected $provider;
    protected $user;

    public function redirectWhenNotAuthenticated($fromUrl) {
        session(['AUTH_RETURN_TO' => $fromUrl]);
        return HerokuController::authURL();
    }

    public function login_url() {
        return HerokuController::authURL();
    }

    public function logout() {
        session(['HEROKU_USERID' => null]);
        return null;
    }

    public function __construct(UserProvider $provider, Request $request) {
        $this->request = $request;
        $this->provider = $provider;
        $this->user = null;
    }

    public function check() {
        return session('HEROKU_USERID') == true;
    }

    public function hasUser() {
        return $this->check();
    }

    public function guest() {
        return session('HEROKU_USERID') != true;
    }

    public function user() {
        static $cached = false;

        $userid = session('HEROKU_USERID');

        if(!$userid)
          return null;

        if($cached && $cached->identifier == $userid)
            return $cached;

        $user = User::where('identifier', $userid)->first();

        $cached = $user;
        return $user;
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
