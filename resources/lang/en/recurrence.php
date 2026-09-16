<?php

/*
 * Recurring event schedules.
 */

return [
    // Describes a template's schedule, e.g. "Every week on Tuesdays"
    'description' => [
        'weekly' => 'Every week on :weekdays',
        'biweekly' => 'Every other week on :weekdays',
        'every_n_weeks' => 'Every week on :weekdays|Every :count weeks on :weekdays',
        // :day is an ordinal day of the month, like "3rd"
        'monthly_date' => 'Every month on the :day',
        // :position is nth_weekday, last_weekday or nth_last_weekday
        'monthly_dow' => 'Every month on the :position',
        // :date is a month and day, like "Jun 3"
        'yearly' => 'Every year on :date',
    ],

    // The schedule menu on the event form
    'options' => [
        'weekly' => 'Every Week on :weekday',
        'biweekly' => 'Every Other Week on :weekday',
        'every_n_weeks' => 'Every N Weeks on :weekday',
        'monthly_date' => 'Every Month on the :day',
        'monthly_dow' => 'Every Month on the :position',
        'yearly' => 'Every Year on :date',
        'how_many_weeks' => 'Repeat every how many weeks?',
    ],

    // e.g. "3rd Tuesday", "last Friday", "2nd last Friday"
    'nth_weekday' => ':ordinal :weekday',
    'last_weekday' => 'last :weekday',
    'nth_last_weekday' => ':ordinal last :weekday',

    // A weekday can fall in at most five different weeks of a month
    'ordinals' => [
        1 => '1st',
        2 => '2nd',
        3 => '3rd',
        4 => '4th',
        5 => '5th',
    ],

    // Plural weekday names, starting with Sunday
    'weekdays' => [
        0 => 'Sundays',
        1 => 'Mondays',
        2 => 'Tuesdays',
        3 => 'Wednesdays',
        4 => 'Thursdays',
        5 => 'Fridays',
        6 => 'Saturdays',
    ],

    // The summary of the revision saved when a template's changes are applied to an event
    'updated_from_template' => 'Updated from the recurring event template',
];
