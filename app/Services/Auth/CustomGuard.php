<?php
namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Guard;

interface CustomGuard extends Guard {

    public function redirectWhenNotAuthenticated($fromUrl);

}

