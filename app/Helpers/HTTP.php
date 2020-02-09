<?php

namespace App\Helpers;

class HTTP {

    public static function user_agent() {
        return 'Meetable ('.env('APP_URL').') Mozilla/5.0';
    }

}
