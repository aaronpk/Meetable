<?php

/*
 * Logging in, creating the admin account, passkeys, and login errors.
 */

return [
    'log_in' => 'Log In',
    'log_in_with_passkey' => 'Log in with a passkey',
    'passkey_login_failed' => 'Something went wrong, refresh and try again!',
    // :command is the command to run, formatted as code
    'admin_without_passkey' => 'An admin account doesn\'t have a passkey yet. To set one up, run :command on the server and open the link it prints.',

    'register' => [
        'title' => 'Create Admin User',
        'intro' => 'Create your admin account now.',
        'name' => 'Name',
        'email' => 'Email',
        'create_user' => 'Create User',
    ],

    'passkey' => [
        'title' => 'Register a Passkey',
        'intro' => 'Register a passkey to protect your account. Setup is not complete until you finish this step!',
        'name_label' => 'Name this passkey',
        'default_name' => 'Passkey',
        'register' => 'Register passkey',
        'register_failed' => 'Something went wrong, try again!',
        'link_used' => 'This link has already been used',
    ],

    'errors' => [
        'title' => 'Error: :error',
        'from_oidc_provider' => 'Error from OpenID Connect Provider',
        'invalid_state' => 'Invalid OAuth State',
        'invalid_state_description' => 'There was a problem with the login process. Double check you are allowing cookies from this domain and try again.',
        'oauth_error' => 'OAuth Error',
        // :provider is the login service, like GitHub or Discord
        'not_completed' => 'The :provider login process did not complete successfully. Please try again.',
        'no_access_token' => 'Unable to get an access token from :provider. Please try again.',
        'no_user_info' => 'Unable to get user info from :provider. Please try again.',
        'not_allowed' => 'User Not Allowed',
        'not_in_allowed_users' => 'Sorry, you are not in the list of allowed users for this website.',
        'not_server_member' => 'Sorry, you are not a member of the Discord server associated with this website.',
        'missing_role' => 'Sorry, you are not assigned the required role in the Discord server.',
        'oidc_server_error' => 'The OpenID Connect server returned an error.',
        'oidc_invalid_response' => 'The OpenID Connect server returned an invalid response.',
        'oidc_no_subject' => 'The OpenID Connect server returned an ID token without a subject.',
    ],
];
