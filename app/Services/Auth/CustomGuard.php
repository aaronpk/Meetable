<?php
namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use App\User;

abstract class CustomGuard implements Guard {

    abstract public function redirectWhenNotAuthenticated($fromUrl);

}
