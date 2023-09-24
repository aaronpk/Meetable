<?php
namespace App\Http\Controllers\Auth;

use Illuminate\Routing\Controller as BaseController;
use Auth, DB;
use App\User;

class AuthController extends BaseController
{

    public function login() {
        if(env('AUTH_METHOD') == 'session') {
            // Check if there is an admin user. If not, it's because
            // they just set up the site and haven't created the admin user yet, so
            // show the page to create the admin user.
            $user = User::where('is_admin', 1)->first();
            if(!$user) {
                return view('auth/register');
            }

            // If there is an admin user, check if there is a webauthn credential
            $credential = DB::table('webauthn_credentials')->where('authenticatable_id', $user->id)->count();
            if(!$credential) {
                if(!Auth::user()) {
                    Auth::login($user);
                }

                // If not, show the page to register a credential
                return view('auth/webauthn');
            }

            if(Auth::user()) {
                return redirect('/');
            }

            return view('auth/login');
        } else {
            if(Auth::user())
                return redirect('/');

            session(['AUTH_RETURN_TO' => request()->headers->get('referer')]);
            return redirect(Auth::guard()->login_url());
        }
    }

    public function create_user() {
        if(env('AUTH_METHOD') == 'session' && !Auth::user()) {
            $users = User::where('is_admin', 1)
              ->join('webauthn_credentials', 'authenticatable_id', '=', 'users.id')
              ->count();
            if($users == 0) {
                // Create the admin user now and log them in
                $user = User::where('is_admin', true)->first();
                if(!$user) {
                    $user = new User;
                    $user->identifier = request('email');
                    $user->email = request('email');
                    $user->name = request('name');
                    $user->is_admin = true;
                    $user->save();
                }
                Auth::login($user);
                return redirect('/login');
            }
        }

        return redirect('/');
    }

    public function logout() {
        $url = Auth::guard()->logout();
        if($url)
            return redirect($url);
        else
            return redirect('/');
    }

}
