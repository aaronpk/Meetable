<?php

/*
 * Text used by the site's JavaScript. Placeholders like :date are filled in by the
 * lang() function in public/assets/script.js.
 */

return [
    // Tooltips on event times. :date is the date and time
    'in_event_timezone' => ':date in event timezone',
    'in_your_timezone' => '(:date in your timezone)',

    'location_lookup_failed' => 'Error looking up location',

    // Voting on the dates of a proposed event
    'vote_failed' => 'Your vote could not be saved. Please reload the page and try again.',
    // :count is the number of people who voted on the date being removed from the form
    'remove_option_with_votes' => ':count people have already voted on this date. Remove it anyway?',
    'proposed_needs_two_dates' => 'Add at least two dates to vote on.',
];
