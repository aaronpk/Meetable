<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Setting;

class ShowLoginLinkFlip extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       if(Setting::value('auth_show_login') !== null)
            Setting::set('auth_hide_login', !Setting::value('auth_show_login'));
        DB::delete('DELETE FROM settings WHERE id = "auth_show_login"');

        if(Setting::value('auth_show_logout') !== null)
            Setting::set('auth_hide_logout', !Setting::value('auth_show_logout'));
        DB::delete('DELETE FROM settings WHERE id = "auth_show_logout"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Setting::value('auth_hide_login') !== null)
            Setting::set('auth_show_login', !Setting::value('auth_hide_login'));
        DB::delete('DELETE FROM settings WHERE id = "auth_hide_login"');

        if(Setting::value('auth_hide_logout') !== null)
            Setting::set('auth_show_logout', !Setting::value('auth_hide_logout'));
        DB::delete('DELETE FROM settings WHERE id = "auth_hide_logout"');
    }
}
