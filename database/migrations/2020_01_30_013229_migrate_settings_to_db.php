<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Setting;

class MigrateSettingsToDb extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $migrated = false;

        $properties = ['LOGO_URL', 'LOGO_WIDTH', 'LOGO_HEIGHT', 'FAVICON_URL', 'GA_ID'];
        foreach($properties as $id) {
            if(env($id)) {
                Setting::set(strtolower($id), env($id));
                $migrated = true;
            }
        }

        $checkboxes = ['ENABLE_WEBMENTION_RESPONSES', 'ENABLE_TICKET_URL', 'SHOW_RSVPS_IN_ICS',
            'AUTH_SHOW_LOGIN', 'AUTH_SHOW_LOGOUT'];
        foreach($checkboxes as $id) {
            Setting::set(strtolower($id), env($id) ? 1 : 0);
            if(env($id))
                $migrated = true;
        }

        $passwords = ['GOOGLEMAPS_API_KEY', 'TWITTER_CONSUMER_KEY', 'TWITTER_CONSUMER_SECRET',
            'TWITTER_ACCESS_TOKEN', 'TWITTER_ACCESS_TOKEN_SECRET'];
        foreach($passwords as $id) {
            Setting::set(strtolower($id), env($id));
            if(env($id))
                $migrated = true;
        }

        if($migrated) {
            Log::info('You can delete the following entries from your .env file:');
            Log::info(implode(', ', array_merge($properties, $checkboxes, $passwords)));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
