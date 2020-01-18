<?php
namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use App\User;

abstract class CustomGuard implements Guard {

    abstract public function redirectWhenNotAuthenticated($fromUrl);

    protected function getUserFromURL($url) {
        $user = User::where('url', $url)->first();

        $created = false;

        if(!$user) {
            $user = new User();
            $user->url = $url;
            $user->api_token = Str::random(80);
            $user->save();
            $created = true;
        }

        return [$user, $created];
    }

}
