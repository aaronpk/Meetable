<?php

/*
 * Event pages, event lists, the archive and tag pages.
 */

return [
    // Labels for the date and location in link previews
    'meta' => [
        'date' => 'Date',
        'location' => 'Location',
    ],

    // The menu of actions for people who can edit the event
    'actions' => [
        'clone' => 'Clone Event',
        'create_recurring' => 'Create Recurring Event',
        'add_photo' => 'Add Photo',
        'add_sub_event' => 'Add Sub-Event',
        'edit_responses' => 'Edit Responses',
        'configure_registration' => 'Configure Registration',
        'enable_registration' => 'Enable Registration',
        'revision_history' => 'Revision History',
        'moderate_responses' => 'Moderate Responses',
        'copy_export_url' => 'Copy Export URL',
    ],

    'meeting_url_needs_timezone' => 'The meeting URL for this event will not be shown because there is no timezone set for this event. Please edit this event to include a timezone.',

    'status' => [
        'confirmed' => 'Confirmed',
        'tentative' => 'Tentative',
        'postponed' => 'Postponed',
        'cancelled' => 'Cancelled',
        'live_now' => 'Live Now',
    ],

    // Shown before the original date of a postponed event
    'tbd_originally' => 'TBD, originally',

    'calendar' => [
        'add_to_calendar' => 'Add to Calendar',
        'tag_feed' => 'Tag Feed',
        'single_event' => 'Single Event',
        'ical' => 'iCal',
        'google' => 'Google Calendar',
    ],

    'code_of_conduct' => 'Code of Conduct',
    'join_meeting' => 'Join the Online Meeting',
    'meeting_link_shown_soon' => 'The meeting link will be shown 15 minutes before the event',
    'notes' => 'Notes',
    'sessions' => 'Sessions',

    // Event lists
    'no_events' => 'No events',
    'no_upcoming_events' => 'No upcoming events',
    'past_events' => 'Past Events',
    'unlisted_events' => 'Unlisted Events',
    'tag_archive' => 'Tag Archive',
    'icalendar_feed' => 'iCalendar Feed',
    'event_count' => ':count event|:count events',

    // Page titles. :site is the name of the website
    'title' => [
        'year' => ':year Events',
        'year_on_site' => ':site in :year',
        'month' => ':site in :month',
        'day' => ':site on :date',
    ],

    // The page that shows an event's time in other timezones
    'local_time' => [
        'invalid_input' => 'Invalid input',
        'event_time' => 'Event Time',
        'your_local_time' => 'Your Local Time',
    ],

    // The page for subscribing to an iCalendar feed
    'subscribe' => [
        'title' => 'Subscribe',
        'instructions' => 'Copy the URL above and subscribe to it in your favorite calendar app!',
    ],
];
