<?php

/*
 * Date and time formats, written as Carbon isoFormat patterns:
 * https://carbon.nesbot.com/docs/#iso-format-available-replacements
 * Month and day names and ordinals are translated for the site's locale.
 */

return [
    // Jun 3, 2031
    'date' => 'MMM D, YYYY',
    // June 3, 2031
    'date_long' => 'MMMM D, YYYY',
    // Tuesday, June 3, 2031
    'date_full' => 'dddd, MMMM D, YYYY',
    // Tuesday, Jun 3, 2031
    'weekday_date' => 'dddd, MMM D, YYYY',
    // Jun 3
    'month_day' => 'MMM D',
    // June 3
    'month_day_long' => 'MMMM D',
    // Tuesday, June 3
    'weekday_month_day_long' => 'dddd, MMMM D',
    // 3, 2031 (the end of a range within one month, like "Jun 3 - 5, 2031")
    'day_year' => 'D, YYYY',
    // June
    'month' => 'MMMM',
    // June 2031
    'month_year' => 'MMMM YYYY',
    // Tuesday
    'weekday' => 'dddd',
    // Tue
    'weekday_short' => 'ddd',
    // 3rd
    'day_ordinal' => 'Do',
    // 6:30pm
    'time' => 'h:mma',
    // 6:30 (the start of a range within the same half of the day, like "6:30 - 8:00pm")
    'time_no_meridiem' => 'h:mm',
    // Jun 3, 2031 6:30pm
    'datetime' => 'MMM D, YYYY h:mma',
    // Tue 6:30pm
    'weekday_time' => 'ddd h:mma',

    // Joins the start and end of a date or time range
    'range' => ':start - :end',
];
