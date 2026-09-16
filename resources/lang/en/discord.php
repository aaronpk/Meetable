<?php

/*
 * Discord notifications: the settings pages, their messages, and the posts sent to Discord.
 */

return [
    'title' => 'Discord Notifications',
    'intro' => 'Post a message to a Discord channel before events with a particular tag start.',

    'setup' => [
        'not_configured' => 'The Discord bot is not configured yet.',
        'steps_intro' => 'To set it up, the site owner needs to:',
        // :bot_settings is a link with the text bot_settings, :env_file and :variable are code
        'step_token' => 'Open the :bot_settings for this Discord application, reset the token, and add it to the :env_file file as :variable',
        'bot_settings' => 'Bot settings',
        // :url is the redirect URL, :oauth_settings is a link with the text oauth_settings
        'step_redirect' => 'Add :url as a redirect URL in the application\'s :oauth_settings',
        'oauth_settings' => 'OAuth2 settings',
        'not_installed' => 'The bot has not been added to the Discord server yet.',
        'needs_admin' => 'Someone with the "Manage Server" permission in Discord needs to add the bot so it can post to channels.',
        'add_bot' => 'Add the bot to Discord',
        'installed_in' => 'The bot is installed in :server.',
        'check_permissions' => 'Make sure the bot can view and send messages in the channels you choose. Use the "Send Test" button to check.',
        // :add_it_again is a link with the text add_it_again
        'reinstall' => 'If the bot was removed from the server, :add_it_again.',
        'add_it_again' => 'add it again',
    ],

    'add_notification' => 'Add Notification',
    'cant_post_in_some' => 'The bot can\'t post in some of these channels.',
    'columns' => [
        'tag' => 'Tag',
        'channel' => 'Channel',
        'when' => 'When',
        'message' => 'Message',
    ],
    'bot_needs_access' => 'Bot needs access',
    'channel_not_found' => 'Channel not found',
    // :time is a duration from durations below, like "15 minutes"
    'time_before' => ':time before',
    'disabled' => 'Disabled',
    'send_test' => 'Send Test',
    'delete_confirm' => 'Delete this notification?',
    'none_yet' => 'No notifications have been set up yet.',

    'form' => [
        'add_title' => 'Add Discord Notification',
        'edit_title' => 'Edit Discord Notification',
        'channels_failed' => 'Could not load the list of channels from Discord.',
        'tag' => 'Event Tag',
        'tag_help' => 'A message will be posted for upcoming events that have this tag.',
        'channel' => 'Discord Channel',
        'choose_channel' => 'Choose a channel',
        'no_category' => 'No category',
        'bot_needs_access_option' => '(bot needs access)',
        'private_channels_help' => 'Channels marked "bot needs access" are private to the bot.',
        'time_before' => 'Time Before the Event',
        'unit' => 'Unit',
        'time_before_help' => 'How long before the event starts to post the message, up to 30 days. Events without a start time are treated as starting at midnight.',
        'message' => 'Message',
        'message_help' => 'This text is posted along with the event\'s name, link, time, location and meeting link.',
        // :role_mention is an example of Discord's role mention syntax
        'mentions_help' => 'You can use Discord formatting, and mention a role with :role_mention. Mentioning @everyone or @here is disabled.',
        'enabled' => 'Enabled',
    ],

    'units' => [
        'minutes' => 'minutes',
        'hours' => 'hours',
        'days' => 'days',
    ],

    'durations' => [
        'minutes' => ':count minute|:count minutes',
        'hours' => ':count hour|:count hours',
        'days' => ':count day|:count days',
    ],

    'messages' => [
        'install_not_verified' => 'The bot installation could not be verified. Please try again.',
        'not_installed' => 'The bot was not installed: :error',
        'installed' => 'The bot was installed in :server',
        'not_in_server' => 'The bot does not appear to be in the Discord server yet. Make sure it was added to the right server.',
        'enter_tag' => 'Enter a tag',
        'whole_number' => 'Enter a whole number for the time before the event',
        'time_range' => 'The time before the event must be between 1 minute and 30 days',
        'message_too_long' => 'The message can be at most 2000 characters',
        'choose_channel' => 'Choose a channel',
        'saved' => 'The notification was saved',
        'deleted' => 'The notification was deleted',
        // :description is test_using_event or test_with_example
        'test_posted' => 'A test message was posted to #:channel :description',
        'test_using_event' => 'using the next ":name" event',
        'test_with_example' => 'with an example event',
        'test_failed' => 'The test message could not be posted to #:channel. :error',
    ],

    // The event used for a test message when no upcoming event has the tag
    'example' => [
        'name' => 'Example Event',
        'summary' => 'There are no upcoming events tagged #:tag, so this is an example of what the notification will look like.',
    ],

    // Field names in the posts sent to Discord
    'embed' => [
        'when' => 'When',
        'where' => 'Where',
        'join' => 'Join',
        'status' => 'Status',
    ],

    'api_error' => 'Discord API error (:status): :message',
    'token_not_configured' => 'The Discord bot token is not configured',
    'the_bot' => 'the bot',
    // :bot is the bot's name
    'access_help' => 'In Discord, edit the channel\'s permissions and allow the ":bot" role or member to View Channel, Send Messages and Embed Links.',
];
