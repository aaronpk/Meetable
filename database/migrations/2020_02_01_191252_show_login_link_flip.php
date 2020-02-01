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
        if(Setting::value('auth_show_login'))
            Setting::set('auth_hide_login', 0);
        else
            Setting::set('auth_hide_login', 1);
        DB::delete('DELETE FROM settings WHERE id = "auth_show_login"');

        if(Setting::value('auth_show_logout'))
            Setting::set('auth_hide_logout', 0);
        else
            Setting::set('auth_hide_logout', 1);
        DB::delete('DELETE FROM settings WHERE id = "auth_show_logout"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Setting::value('auth_hide_login'))
            Setting::set('auth_show_login', 0);
        else
            Setting::set('auth_show_login', 1);
        DB::delete('DELETE FROM settings WHERE id = "auth_hide_login"');

        if(Setting::value('auth_hide_logout'))
            Setting::set('auth_show_logout', 0);
        else
            Setting::set('auth_show_logout', 1);
        DB::delete('DELETE FROM settings WHERE id = "auth_hide_logout"');
    }
}
