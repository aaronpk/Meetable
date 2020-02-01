<?php
namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\User;
use Illuminate\Support\Str;
use App\Events\UserCreated;
use App\Http\Controllers\HerokuController;

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
        $url = session('HEROKU_USERID');
        return $url == true;
    }

    public function guest() {
        $url = session('HEROKU_USERID');
        return $url != true;
    }

    public function user() {
        static $cached = false;

        $url = session('HEROKU_USERID');

        if(!$url)
          return null;

        if($cached && $cached->url == $url)
            return $cached;

        $user = User::where('url', 'heroku://'.$url)->first();

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
