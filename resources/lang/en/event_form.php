<?php

/*
 * Creating, editing, importing and cloning events, and the recurring events list.
 */

return [
    'actions' => 'Actions',
    'delete_event' => 'Delete Event',

    // :name is the event's name
    'heading' => [
        'add' => 'Add an Event',
        'editing' => 'Editing ":name"',
        'edit_recurring' => 'Edit Recurring ":name"',
        'cloning' => 'Cloning ":name"',
        'create_recurring' => 'Create Recurring ":name"',
    ],

    'problem_creating' => 'There was a problem creating the event',
    'problem_saving' => 'There was a problem saving the event',
    'zoom_failed' => 'Failed to create the Zoom meeting. The changes were not saved.',
    // :field is one of url_field_names below
    'url_must_be_http' => 'The :field must be an http or https link.',
    'url_field_names' => [
        'website' => 'website',
        'tickets_url' => 'registration URL',
        'code_of_conduct_url' => 'code of conduct URL',
        'meeting_url' => 'meeting URL',
        'video_url' => 'video URL',
        'notes_url' => 'notes URL',
        'cover_image' => 'cover image',
    ],

    // :parent is a link to the parent event
    'creating_under_parent' => 'Creating an event under :parent',
    'editing_under_parent' => 'Editing an event under :parent',
    'hide_from_main_feed' => 'Hide from main feed (Only show this event on the parent event)',

    'creating_template' => 'You are creating a recurring event template. After you save this template, copies will be created based on the schedule you define below.',
    'template_explanation' => 'This is a template event and does not appear on the calendar itself. Events are created as a copy of this event based on the schedule defined below.',
    'template_changes' => 'Changes you make will apply to future events and not to past events.',

    'name_question' => 'What\'s the name of the event?',

    'cover_image_question' => 'Add a cover image (optional)',
    'choose_image' => 'Choose an image...',
    'cover_image_help' => 'cover images should be at least 1440px wide and will be cropped to 1440x640',

    'location_question' => 'Where will the event take place?',
    'search_location' => 'Search for a location',
    'venue' => 'Venue',
    'address' => 'Address',
    'city' => 'City',
    'state' => 'State',
    'country' => 'Country',

    'when_question' => 'When is the event?',
    'next_occurrence_question' => 'When is the next occurrence of the event?',
    'optional' => '(optional)',
    'start_date' => 'Start Date',
    'end_date' => 'End Date (optional)',
    'end_date_help' => 'for multi-day events',
    'start_time' => 'Start Time',
    'start_time_help' => 'leave start time blank for multi-day events',
    'end_time' => 'End Time (optional)',
    'end_time_help' => 'leave end time blank for multi-day events',
    'timezone' => 'Timezone',
    'timezone_help' => 'provide a timezone for online events and to help sort events on the same day',

    // Proposing several dates for people to vote on, instead of setting the date
    'propose_dates' => 'Propose several dates and let people vote, instead of setting a date',
    'propose_dates_help' => 'The event is listed under Proposed Events until a date is chosen.',
    'proposed_dates_help' => 'Add at least two dates to vote on. Times are optional and use the timezone below. Give a date an end date to propose a multi-day event.',
    'proposed_edit_notice' => 'The date is set by choosing one of the proposed dates on the event page. Votes are kept for dates that stay in this list. Removing or changing a date removes its votes.',
    'add_another_date' => 'Add another date',
    'remove_date' => 'Remove this date',
    'option_votes' => ':count vote|:count votes',
    'proposed_needs_two_dates' => 'Add at least two dates to vote on.',
    'proposed' => [
        'choose_an_option' => 'Choose one of the proposed dates to schedule the event.',
        // The edit summary written when a date is chosen. :date is the chosen date
        'finalized' => 'Scheduled for :date',
    ],

    'repeat_question' => 'How often do you want to repeat this event?',

    'details' => 'Details',
    'website' => 'Website',
    'website_help' => 'provide a link to the event\'s main website if any',
    'registration_url' => 'Registration URL',
    'registration_url_help' => 'if the event requires registration, link to the registration page here. this will also disable RSVPs on this website.',
    'code_of_conduct' => 'Code of Conduct',
    'code_of_conduct_help' => 'provide one or more URLs to codes of conduct that are applicable to this event',
    'schedule_zoom' => 'Schedule a Zoom Meeting',
    // :email is the Zoom account's email address
    'schedule_zoom_help' => 'check the box above to schedule a zoom meeting for this event. the meeting url will be shown on the event page 15 minutes before the start. note: the host will need to log in to zoom as :email to start the meeting.',
    'meeting_url' => 'Meeting URL',
    // :shown_when is meeting_url_help_shown_when, in bold
    'meeting_url_help' => 'if this is a virtual event, enter a url to join the virtual meeting. :shown_when, and hidden afterwards',
    'meeting_url_help_shown_when' => 'this will be shown only 15 minutes before the event start',
    'notes_url' => 'Link to Notes',
    'notes_url_help' => 'link to etherpad or archived notes for this event',
    'summary' => 'Summary',
    'description' => 'Description',
    'markdown_supported' => 'markdown and HTML are supported',
    'tags' => 'Tags',
    'tags_help' => 'space separated, lowercase',
    'video_url' => 'Video URL',
    'video_url_help' => 'After the event is over, you can add a link to a recording here. YouTube and Vimeo videos will be embedded in the page, otherwise only the link will be displayed.',
    'status' => 'Status',
    'unlisted' => 'Unlisted event (prevents this event from showing on the home page and other feeds)',
    'edit_summary' => 'Edit Summary',
    'edit_summary_help' => 'a brief description of your changes',

    'import' => [
        'title' => 'Import an Event',
        'help' => 'Enter a URL to import that event, such as an event on another Meetable instance or an ICS feed. Microformats and ICS are supported. If an ICS feed contains several events, the one that starts first is imported.',
        'preview' => 'Preview',
    ],

    'photo' => [
        'title' => 'Add a Photo',
        'choose_photo' => 'Choose a photo...',
        'add_photo' => 'Add Photo',
    ],

    'registration' => [
        'title' => 'Configure Event Registration',
    ],

    'templates' => [
        'title' => 'Recurring Events',
        'none_yet' => 'There are no recurring events yet. You can schedule a recurring event by creating an event template from an existing event.',
        'see_upcoming' => 'See upcoming events',
        // :existing_event is a link with the text existing_event
        'how_to' => 'You can schedule a recurring event by creating an event template from an :existing_event.',
        'existing_event' => 'existing event',
        'starting' => 'starting :date',
        'future_occurrences' => 'Future occurrences:',
    ],
];
