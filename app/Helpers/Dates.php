<?php
namespace App\Helpers;

use Carbon\Carbon;
use DateTimeInterface;

/**
 * Formats dates using the patterns in resources/lang/{locale}/dates.php, so month and
 * day names, ordinals and the order of the parts follow the site's language.
 */
class Dates {

    // $date can be a DateTime, a date string or a Unix timestamp. A DateTime keeps its timezone.
    public static function format($date, $key) {
        if($date instanceof DateTimeInterface)
            $date = Carbon::instance($date);
        elseif(is_numeric($date))
            $date = Carbon::createFromTimestamp($date, date_default_timezone_get());
        else
            $date = Carbon::parse($date);

        return $date->locale(app()->getLocale())->isoFormat(__('dates.'.$key));
    }

}
