<?php
namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Auth;

class AuthController extends BaseController
{

    public function login() {
        session(['AUTH_RETURN_TO' => request()->headers->get('referer')]);
        return redirect(Auth::guard()->login_url());
    }

    public function logout() {
        $url = Auth::guard()->logout();
        if($url)
            return redirect($url);
        else
            return redirect('/');
    }

}
