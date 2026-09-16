<?php

/*
 * RSVPs, photos, blog posts, comments and other responses to events.
 */

return [
    'rsvps' => 'RSVPs',
    'rsvp_going' => 'I\'m Going!',
    'rsvp_went' => 'I Went',
    'remote_attendees' => 'Remote Attendees',
    'maybe' => 'Maybe',
    'cant_go' => 'Can\'t Go',

    'alt_text_placeholder' => 'alt text',
    // :source is a link to where the photo came from
    'photo_via' => 'via :source',

    'blog_posts' => 'Blog Posts',
    // :author is a link to the author
    'blog_post_by' => 'by :author',
    'blog_post_by_on' => 'by :author on :date',

    'comments' => 'Comments',

    // :name is the name of the person who liked the event
    'likes_this' => ':name likes this',
    // :date is when it was received, :source is a link to the website that sent it
    'webmention_received' => 'Webmention Received :date from :source',
    // :date is when it was added, :author is a link to the person who added it
    'added_by' => 'Added :date by :author',

    // Editing and moderating responses
    'responses_on' => 'Responses on ":name"',
    'pending_responses' => 'Pending Responses',
    'delete_warning' => 'Deleting a response will prevent that webmention URL from ever appearing again even if it is re-sent.',
    'view_details' => 'View Details',
    'approve' => 'Approve',

    // The details of a response
    'details' => [
        'author_name' => 'Author Name',
        'author_photo' => 'Author Photo',
        'author_url' => 'Author URL',
        'name' => 'Name',
        'content' => 'Content',
        'rsvp' => 'RSVP',
        'photos' => 'Photos',
        'url' => 'URL',
        'source_url' => 'Webmention Source URL',
        'post_type' => 'Post Type',
        'published_at' => 'Published At',
        'created_at' => 'Created At',
        'parsed_data' => 'Parsed Post Data',
    ],

    // The form for sending a webmention from the browser, and the messages it returns
    'webmention' => [
        'your_url' => 'Your URL',
        'event_url' => 'Event URL',
        'send' => 'Send Webmention',
        'invalid_target' => 'Invalid target URL',
        'target_not_event' => 'Target URL was not a valid event URL. Webmentions are only supported to event URLs.',
        'event_cancelled' => 'Webmentions are not accepted to cancelled events',
        'redirected_duplicate' => 'This source URL redirected to a response that has already been received so this response was deleted',
        'parse_problem' => 'There was a problem parsing the source URL',
        'reposts_not_accepted' => 'Reposts are not accepted',
        'deleted' => 'The webmention from this URL has been deleted from the event and won\'t be added again',
        'not_approved' => 'Your webmention was received, but was not automatically approved. If you log in with the same domain as your RSVP, it will be automatically approved in the future.',
        'fetch_failed' => 'The source URL could not be fetched',
        'parse_failed' => 'The source URL could not be parsed',
    ],
];
