<?php
namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\User;
use Illuminate\Support\Str;
use App\Events\UserCreated;
use App\Http\Controllers\GitHubController;

class GitHubGuard extends CustomGuard {

    protected $request;
    protected $provider;
    protected $user;

    public function redirectWhenNotAuthenticated($fromUrl) {
        session(['AUTH_RETURN_TO' => $fromUrl]);
        return GitHubController::githubAuthURL();
    }

    public function login_url() {
        return GitHubController::githubAuthURL();
    }

    public function logout() {
        session(['GITHUB_USER' => null]);
        return null;
    }

    public function __construct(UserProvider $provider, Request $request) {
        $this->request = $request;
        $this->provider = $provider;
        $this->user = null;
    }

    public function check() {
        return session('GITHUB_USER') == true;
    }

    public function guest() {
        return session('GITHUB_USER') != true;
    }

    public function user() {
        static $cached = false;

        $url = session('GITHUB_USER');

        if(!$url)
          return null;

        if($cached && $cached->url == $url)
            return $cached;

        $user = User::where('url', $url)->first();

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
