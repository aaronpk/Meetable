<?php
namespace App\Http\Controllers\Auth;

use Illuminate\Routing\Controller as BaseController;
use Auth, Cache, DB;
use App\User;
use Laravel\Passkeys\Passkey;

class AuthController extends BaseController
{

    public function login() {
        if(env('AUTH_METHOD') == 'session') {
            // Check if there is an admin user. If not, it's because
            // they just set up the site and haven't created the admin user yet, so
            // show the page to create the admin user.
            if(!User::where('is_admin', 1)->exists()) {
                return view('auth/register');
            }

            if($user = Auth::user()) {
                // Someone who just created the admin account, or followed a passkey link,
                // is signed in without a passkey and needs to register one
                if(!Passkey::where('user_id', $user->id)->exists()) {
                    return view('auth/webauthn');
                }

                return redirect('/');
            }

            // Never sign anyone in from here. An admin without a passkey needs a
            // one-time link generated on the server.
            $admin_without_passkey = User::where('is_admin', 1)
              ->whereNotExists(function($query){
                  $query->select(DB::raw(1))->from('passkeys')->whereColumn('passkeys.user_id', 'users.id');
              })
              ->exists();

            return view('auth/login', [
                'admin_without_passkey' => $admin_without_passkey,
            ]);
        } else {
            if(Auth::user())
                return redirect('/');

            session(['AUTH_RETURN_TO' => \App\Helpers\Uri::same_origin_path(request()->headers->get('referer'))]);
            return redirect(Auth::guard()->login_url());
        }
    }

    public function create_user() {
        // The admin account can only be created from the web while there are no admins at all
        if(env('AUTH_METHOD') == 'session' && !Auth::user() && !User::where('is_admin', 1)->exists()) {
            $user = new User;
            $user->identifier = request('email');
            $user->email = request('email');
            $user->name = request('name');
            $user->is_admin = true;
            $user->save();

            Auth::login($user);
            session()->regenerate();
            return redirect('/login');
        }

        return redirect('/');
    }

    // Opened from the link printed by `php artisan user:passkey-link`, lets that user register a passkey
    public function passkey_link(User $user) {
        if(env('AUTH_METHOD') != 'session')
            abort(404);

        // The signature proves the server issued the link and that it hasn't expired.
        // Record the nonce as used here so the link only works once.
        $nonce = (string)request('nonce');
        if(!$nonce || !Cache::add('passkey-link-used:'.$nonce, true, now()->addDay()))
            abort(403, 'This link has already been used');

        Auth::login($user);
        session()->regenerate();

        return view('auth/webauthn');
    }

    public function logout() {
        $url = Auth::guard()->logout();
        if($url)
            return redirect($url);
        else
            return redirect('/');
    }

}
