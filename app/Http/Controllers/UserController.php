<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller as BaseController;
use App\Setting;
use DateTime, DateTimeZone, Exception;
use Gate, Auth;

class UserController extends BaseController
{

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function profile() {
        Gate::authorize('logged-in');

        return view('profile');
    }

    public function refresh_profile() {
        Gate::authorize('logged-in');

        $user = Auth::user();
        $user->fetchProfileInfo();

        return redirect('/profile');
    }


}
