<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Setting;

class MigrateConfigSettings extends Command {

    protected $signature = 'migrate:settings';
    protected $description = 'Migrate config settings from .env to the database';

    public function handle() {

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

        $passwords = ['GOOGLEMAPS_API_KEY'];
        foreach($passwords as $id) {
            Setting::set(strtolower($id), env($id));
            if(env($id))
                $migrated = true;
        }

        $this->info('You can delete the following entries from your .env file:');
        $this->info(implode(', ', array_merge($properties, $checkboxes, $passwords)));

    }
}
