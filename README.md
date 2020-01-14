# Meetable

Meetable is a minimal events aggregator website.

You can see a live version of this project at:

https://events.indieweb.org

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

* PHP 7.2+
* [Composer](https://getcomposer.org)
* MySQL
* Redis
* [imageproxy](https://github.com/willnorris/imageproxy)

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

In a production system you'll want to make sure the background worker script is running

```bash
php artisan queue:listen
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

  location /public {
    alias /web/sites/events.example.org/storage/app/public;
  }
}
```

### Authentication

This project provides no authentication mechanism itself. Instead, it relies on the web server being able to authenticate users somehow, and setting an environment variable when users are logged in.

When the `Remote-User` header is present, this app considers users logged-in with the value of that header as their unique user ID, which is expected to be a URL.

You can set up any authentication mechanism in front of the app, such as setting a cookie on the parent domain, or by using a project such as [Vouch Proxy](https://github.com/vouch/vouch-proxy) to offload authentication to an external OAuth service.

As long as the app sees a `Remote-User` header, users will be considered logged in.


#### Vouch Proxy

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


### imageproxy

Install https://github.com/willnorris/imageproxy

Run with a config such as:

```
imageproxy \
  -cache memory:500 \
  -cache /path/to/storage/cache \
  -baseURL https://events.example.org/ \
  -signatureKey 1234 \
  -allowHosts events.example.org \
  -referrers \*.example.org \
  -addr 127.0.0.1:8090
```

Make sure to replace the URL in this example with your own

Configure your web server to proxy `/img/` to the imageproxy, e.g. for nginx:

```
  location /img/ {
    proxy_pass http://localhost:8090/;
  }
```

## License

Copyright 2020 by Aaron Parecki. Available under the MIT license.

