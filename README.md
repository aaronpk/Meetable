# Meetable

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)

Meetable is a minimal events aggregator website.

You can see a live version of this project at:

* https://events.indieweb.org
* https://events.oauth.net

## Features

* **Discovery** List of upcoming events on the home page, and archive view of past events.
* **Tags** Events can have one or more tags. Commonly-used tags are shown on the home page as well as the "discover" page.
* **iCal feeds** All lists of events have an iCal feed (home page, tag pages, etc) so you can subscribe to them in an external calendar.
* **Add to Calendar** Events have an "Add to Calendar" link that exports either an iCal file or links to Google Calendar.

### Event Pages

Events have a permalink that contains

* cover photo
* event name, date/time and location details
* a link to an external website and ticket URL
* a description of the event, which supports markdown and basic HTML formatting
* a link to a timezone converter
* RSVPs (an RSVP button appears for logged-in users)
* photos, blog posts, and notes about the event

When logged in, you can add photos directly to an event page. Event pages also accept [webmentions](https://webmention.net) so that people can add photos and notes to the page from their own websites.


## Setup

### Requirements

* PHP 8.2+
* [Composer](https://getcomposer.org)
* MySQL/MariaDB
* Optional: Redis

### Installation

This project is based on [Laravel](https://laravel.com), so you can defer to their instructions if you encounter any issues.

Clone the source into a folder

```bash
git clone https://github.com/aaronpk/Meetable.git
cd Meetable
```

Install the project's dependencies

```bash
composer install
```

Make sure the `storage` folder is writable by the web server

```bash
sudo chown -R www-data: storage
```

Copy `.env.example` to `.env` and fill it out following the instructions in the file

```bash
cp .env.example .env
```

Once you've configured everything in the `.env` file, you can run the migrations to set up the database

```bash
php artisan migrate
```

In a production system you'll want to make sure the background worker script is running:

```bash
php artisan queue:listen
```

Alternatively, you can set up a cron job to run every minute which will process any jobs on the queue:

```bash
php artisan queue:work --stop-when-empty
```


### Web Server

Configure your web server to serve the project's `public` folder from the domain name you've set up.

For nginx:

```
server {
  listen 443 ssl http2;
  server_name  events.example.org;

  ssl_certificate /etc/letsencrypt/live/events.example.org/fullchain.pem;
  ssl_certificate_key /etc/letsencrypt/live/events.example.org/privkey.pem;

  root /web/sites/events.example.org/public;

  index index.php;
  try_files $uri /index.php?$args;

  location ~* \.php$ {
    fastcgi_pass    php-pool;
    fastcgi_index   index.php;
    fastcgi_split_path_info ^(.+\.php)(.*)$;
    include fastcgi_params;
    fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
  }
}
```

If you're using the `local` storage driver to store uploaded images on disk, then make sure to symlink the storage folder:

```
php artisan storage:link
```


### Authentication

There are a few different ways to handle user authentication depending on how you'd like to set it up. You can use GitHub so that GitHub users can log in, you can use your own custom authentication mechanism configured externally, either via OpenID Connect or by setting an HTTP header in your web server, or you can use the site in single-user mode with the admin user logging in with a passkey.

In your configuration file, you'll need to tell the project which authentication method to use:

```bash
AUTH_METHOD=
```

Provide one of the supported values for `AUTH_METHOD`:

* `session` (passkey login)
* `github`
* `oidc`
* `vouch`
* `heroku`

You can choose whether or not you want a "log in/out" link to appear in the top navbar. When using single-sign-on with Vouch, it may be preferable to not have a log out button since that would log them out from more than just this website. For a single-user site where only you will be logging in to manage events, it is best to hide the login link so visitors don't try to log in. For a multi-user configuration, you can show both links.

```bash
AUTH_SHOW_LOGIN=true
AUTH_SHOW_LOGOUT=false
```

#### Passkey Authentication

If you don't want to create any new dependencies when you set this up, you can use the built in passkey authentication to allow only yourself, the admin user, to log in.

After you install the app, when you click "Log In", you'll be prompted to create your admin user account, specifying your email address and enrollling a passkey. From that point on, there is no way for users to be created in the web interface, and you'll need to log in with your passkey in the future.

This is a good option if you want to quickly set up the site and expect that you are the only one who will be logging in to manage the content.

If you expect multiple users to log in to manage the content, use one of the multi-user options below.


#### GitHub Authentication

With GitHub authentication, any GitHub user will be able to log in to the application. You can also configure it to allow only certain users to log in if you wish, and any other user will see an error message if they try to log in.

You'll need to [create a GitHub OAuth application](https://github.com/settings/developers) and include the app's client ID and secret in the config file. In the GitHub app settings, set the callback URL to `https://events.example.org/auth/github`.

```bash
AUTH_METHOD=github
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
```

If you want to restrict who can log in, define a space-separated list of usernames in the config file. If someone attempts to log in via GitHub and is not in this list, their login will be blocked.

```
GITHUB_ALLOWED_USERS=user1 user2 user3
```

To set specific users as admins when they log in, define them in the config file:

```
GITHUB_ADMIN_USERS=user1 user2
```


#### Heroku Authentication

You can use Heroku's OAuth to log in to this site. This is intended to be used to quickstart the Heroku deploy button, and is really only meant to be used when a single user will be logging in, since it's somewhat difficult to configure after the initial setup.

If you are using the Heroku deploy button then this will all be configured automatically. If you'd like to set it up manually, the instructions are below.

You'll need to create a Heroku OAuth app, which you can do [from the command line](https://devcenter.heroku.com/articles/platform-api-reference#oauth-client-create). Set the redirect URL to `https://events.example.org/auth/heroku`. You'll need the client ID and secret that are provided after creating the app.

```
AUTH_METHOD=heroku
HEROKU_CLIENT_ID=
HEROKU_CLIENT_SECRET=
```

There are no other config options to set permissions. The first user to log in will be the site admin. No other users will be able to log in after that. If you'd like, you can manually add Heroku user IDs to the database if you really do want other Heroku users to log in.


#### OpenID Connect

You can configure Meetable to authenticate users via an OpenID Connect server. This is useful if you are already using a service like Auth0 or Okta and want to add this as another app for your organization.

You'll need to register an application at your OpenID Connect service and configure the redirect URL appropriately. You can also configure IdP-initiated login in order to let your users log in to Meetable from their dashboard at the OpenID Connect server. The templates for each URL are below:

* Redirect URI: `https://events.example.org/auth/oidc`
* Initiate Login URI: `https://events.example.org/auth/oidc/initiate`

You will also need to add the following configuration to Meetable:

```
OIDC_AUTHORIZATION_ENDPOINT=https://authorization-server.com/authorize
OIDC_TOKEN_ENDPOINT=https://authorization-server.com/token
OIDC_CLIENT_ID=
OIDC_CLIENT_SECRET=
```

By default, all users that exist at this server will be allowed to log in to Meetable. To limit who can log in, you can of course create policies at your OpenID Connect server to prevent users from being able to log in. If that's not an option, you can hard-code a list of user IDs who are allowed to log in here. Enter a space-separated string of each user's `sub` ID in the config:

```
OIDC_ALLOWED_USERS=sub1 sub2 sub3
```

To set specific users as admins when they log in, define their `sub` IDs in the config file:

```
OIDC_ADMIN_USERS=sub1 sub2
```


#### Vouch Proxy

In this configuration, this project provides no authentication mechanism itself. Instead, it relies on the web server being able to authenticate users somehow, and setting an environment variable when users are logged in.

When the `Remote-User` header is present, this app considers users logged-in with the value of that header as their unique user ID, which is expected to be a URL. As long as the app sees a `Remote-User` header, users will be considered logged in.

[Vouch Proxy](https://github.com/vouch/vouch-proxy) can offload authentication to an external OAuth service, and can be configured to set the HTTP `Remote-User` header that this project looks for.

Configure the application to use Vouch and tell it the hostname of your Vouch server.

```bash
AUTH_METHOD=vouch
VOUCH_HOSTNAME=sso.example.org
```

Below is an example configuration for using Vouch proxy to set the `Remote-User` header.

Deploy Vouch behind the hostname `sso.example.org`

```
server {
  listen 443 ssl http2;
  server_name sso.example.org;

  ssl_certificate /etc/letsencrypt/live/sso.example.org/fullchain.pem;
  ssl_certificate_key /etc/letsencrypt/live/sso.example.org/privkey.pem;

  access_log  /usr/local/nginx/logs/sso.access.log  main;
  error_log  /usr/local/nginx/logs/sso.error.log;

  location / {
    proxy_set_header  Host  sso.example.org;
    proxy_pass        http://127.0.0.1:9244;
  }
}
```

See [Vouch examples](https://github.com/vouch/vouch-proxy/tree/master/config) for example configuration of the actual Vouch system.

In the `server` block for the events site, insert the following:

```
  auth_request /vouch-validate;
  auth_request_set $auth_user $upstream_http_x_vouch_user;

  location = /vouch-validate {
    proxy_pass https://sso.example.org/validate;
    proxy_pass_request_body     off;

    proxy_set_header Content-Length "";
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;

    # these return values are fed to the @error401 call
    auth_request_set $auth_resp_jwt $upstream_http_x_vouch_jwt;
    auth_request_set $auth_resp_err $upstream_http_x_vouch_err;
    auth_request_set $auth_resp_failcount $upstream_http_x_vouch_failcount;
  }
```

In the `location ~* \.php` block which proxies requests to the PHP handler, add the following to turn the `$auth_user` variable set by Vouch into the `REMOTE_USER` setting read by PHP:

```
    fastcgi_param   REMOTE_USER $auth_user;
    fastcgi_param   HTTP_REMOTE_USER $auth_user;
```

If you want your website to be visible even to logged-out users, make sure Vouch is configured with `publicAccess: true` to avoid sending back an error page when users are not logged in.

All users created in this mode will be created as regular users. You'll need to manually configure specific accounts as admin accounts in the database after they log in.

### Permissions

Permissions in this site can be configured to support a few different use cases.

You can choose whether all users or just admin users can manage events and the website text.

```
ALLOW_MANAGE_EVENTS=users
ALLOW_MANAGE_EVENTS=admins
ALLOW_MANAGE_SITE=users
ALLOW_MANAGE_SITE=admins
```

Currently `ALLOW_MANAGE_EVENTS` enables access to everything around events, including creating, editing, and deleting events, as well as adding and deleting responses.


## Installing on Heroku manually

```
# Make sure to set the organization if you're adding this to an account that is not your personal account
export HEROKU_ORGANIZATION=

git clone https://github.com/aaronpk/Meetable.git

cd Meetable

heroku git:remote -a your-heroku-app-name

# Add MySQL
heroku addons:create cleardb:ignite --as=DATABASE

# Add CloudCube (AWS S3 storage)
heroku addons:create cloudcube:free

# Deploy the app to Heroku
git push heroku master

# Visit the app in your browser and continue the setup there
# Once you finish the setup walkthrough, it will give you a series of `heroku config:set` commands to run
# Run all the provided `heroku config:set` commands...

heroku config:set ...
...
```


## License

Copyright 2020-2023 by Aaron Parecki. Available under the MIT license.

