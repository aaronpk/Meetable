<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller as BaseController;
use App\Setting;
use DateTime, DateTimeZone, Exception;

class SettingsController extends BaseController
{
    // Access control is managed in the route definition

    public function get() {
        return view('settings');
    }

    public function post() {
        foreach(['add_an_event'] as $id) {
            Setting::set($id, request($id));
        }
        session()->flash('settings-saved', 'The settings have been saved');
        return redirect(route('settings'));
    }

}
