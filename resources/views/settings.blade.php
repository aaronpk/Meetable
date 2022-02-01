@extends('layouts/main')
@php
use App\Setting;
@endphp

@section('content')
<section class="section">

<form action="{{ route('settings-save') }}" method="post" class="settings-form">

    @if($message = session('settings-saved'))
        <article class="message is-primary">
            <div class="message-body">
                {{ $message }}
            </div>
        </article>
    @endif


    <div class="tabs is-boxed">
        <ul>
            <li class="is-active" data-tab="messages"><a>Messages</a></li>
            <li data-tab="design"><a>Design</a></li>
            <li data-tab="features"><a>Site Features</a></li>
            <li data-tab="services"><a>Services</a></li>
        </ul>
    </div>

    <div class="tab-content" id="tab-features">
        <div class="field">
            <label class="label">
                <input type="checkbox" name="enable_ticket_url" value="1" {{ Setting::value('enable_ticket_url') ? 'checked="checked"' : ''}}>
                Enable Registration URL
            </label>
            <p class="help">Show or hide the "Registration URL" field on events.</p>
        </div>

        <!--
        <div class="field">
            <label class="label">
                <input type="checkbox" name="enable_registration" value="1" {{ Setting::value('enable_registration') ? 'checked="checked"' : ''}}>
                Enable Built-In Registration
            </label>
            <p class="help">When checked, registration can be enabled on this website for events.</p>
        </div>
        -->

        <div class="field">
            <label class="label">
                <input type="checkbox" name="enable_unlisted_events" value="1" {{ Setting::value('enable_unlisted_events') ? 'checked="checked"' : ''}}>
                Enable Unlisted Events
            </label>
            <p class="help">When checked, events can be marked as "unlisted", preventing them from showing up on the home page and all feeds.</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="enable_rsvps" value="1" {{ Setting::value('enable_rsvps') ? 'checked="checked"' : ''}}>
                Enable RSVPs
            </label>
            <p class="help">When checked, events will have an "RSVP" button for logged-in users and will show who has RSVPd. When unchecked, RSVP webmentions will not be shown either.</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="show_rsvps_in_ics" value="1" {{ Setting::value('show_rsvps_in_ics') ? 'checked="checked"' : ''}}>
                Show RSVPs in ICS Feeds
            </label>
            <p class="help">When checked, event names in the ics feeds will include the names of people who have RSVP'd to the event.</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="enable_webmention_responses" value="1" {{ Setting::value('enable_webmention_responses') ? 'checked="checked"' : ''}}>
                Enable Webmention Responses
            </label>
            <p class="help">Check this option to let people post comments and photos on events via <a href="https://webmention.net">Webmention</a>.</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="auth_hide_login" value="1" {{ Setting::value('auth_hide_login') ? 'checked="checked"' : ''}}>
                Hide Log In Button
            </label>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="auth_hide_logout" value="1" {{ Setting::value('auth_hide_logout') ? 'checked="checked"' : ''}}>
                Hide Log Out Button
            </label>
        </div>

    </div>


    <div class="tab-content" id="tab-messages">
        <div class="field">
            <label class="label">Add an Event</label>
            <textarea class="input" name="add_an_event" style="max-height: none; height: 25vh">{{ Setting::value('add_an_event') }}</textarea>
            <div class="help">You can edit the text that appears on the "Add an Event" page for users. Use this to describe what kinds of events should be added to the website. Markdown and HTML are supported.</div>
        </div>
    </div>



    <div class="tab-content" id="tab-design">
        <div class="field">
          <div class="control">
            <label class="label">Logo URL</label>
            <input class="input" type="url" value="{{ Setting::value('logo_url') }}" name="logo_url">
          </div>
          <p class="help">Provide the URL to a logo to show in the top left corner of the website. If blank, just the website name will be displayed.</p>
        </div>

        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">Logo Width (optional)</label>
                <input class="input" type="text" name="logo_width" autocomplete="off" value="{{ Setting::value('logo_width') }}">
            </div>

            <div class="control is-expanded">
                <label class="label">Logo Height (optional)</label>
                <input class="input" type="text" name="logo_height" autocomplete="off" value="{{ Setting::value('logo_height') }}">
            </div>
            <p class="help">Depending on your image, you may need to define the width and/or height. Make sure to include CSS units such as "80px"</p>
        </div>

        <div class="field">
            <div class="control">
                <label class="label">Favicon URL</label>
                <input class="input" type="url" value="{{ Setting::value('favicon_url') }}" name="favicon_url">
            </div>
            <p class="help">Provide the URL to a favicon to use on the website.</p>
        </div>

        <div class="field">
          <div class="control">
            <label class="label">Home Page Social Image</label>
            <input class="input" type="url" value="{{ Setting::value('home_social_image_url') }}" name="home_social_image_url">
          </div>
          <p class="help">Provide the URL to an image to use for the home page social sharing card. This will not be displayed on the website, it will only appear in Twitter/Slack/Facebook previews of the home page.</p>
        </div>

        <div class="field">
          <div class="control">
            <label class="label">Home Page Meta Description</label>
            <input class="input" type="text" value="{{ Setting::value('home_meta_description') }}" name="home_meta_description">
          </div>
          <p class="help">This text will be used as the meta description of the home page, as well as the description for Twitter/Facebook cards.</p>
        </div>

        <div class="field">
          <div class="control">
            <label class="label">Custom CSS</label>
            <textarea class="textarea" name="custom_global_css" rows="8">{{ Setting::value('custom_global_css') }}</textarea>
          </div>
          <p class="help">Write custom CSS that will be included on every page. This can be used to, for example, add a site banner.</p>
        </div>


    </div>


    <div class="tab-content" id="tab-services">
        <div class="field">
          <div class="control">
            <label class="label">Google Maps API Key</label>
            <input class="input" type="password" value="{{ Setting::value('googlemaps_api_key') ? '********' : '' }}" name="googlemaps_api_key" autocomplete="off">
          </div>
          <p class="help">In order to search for locations and show maps, you'll need to get a <a href="https://developers.google.com/maps/documentation/javascript/get-api-key">Google Maps API key</a></p>
        </div>

        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">Twitter Consumer Key</label>
                <input class="input" type="password" value="{{ Setting::value('twitter_consumer_key') ? '********' : '' }}" name="twitter_consumer_key" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">Twitter Consumer Secret</label>
                <input class="input" type="password" value="{{ Setting::value('twitter_consumer_secret') ? '********' : '' }}" name="twitter_consumer_secret" autocomplete="off">
            </div>
        </div>

        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">Twitter Access Token</label>
                <input class="input" type="password" value="{{ Setting::value('twitter_access_token') ? '********' : '' }}" name="twitter_access_token" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">Twitter Access Token Secret</label>
                <input class="input" type="password" value="{{ Setting::value('twitter_access_token_secret') ? '********' : '' }}" name="twitter_access_token_secret" autocomplete="off">
            </div>
        </div>
        <p class="help">Create a Twitter app and provide these values in order to be able to quickly add tweets to event pages as comments or photos</p>
        <br>

        <div class="field">
          <div class="control">
            <label class="label">Analytics</label>
            <textarea class="input" name="analytics" style="height:8em; font-family:courier; font-size: 0.8em;">{{ Setting::value('analytics') }}</textarea>
          </div>
          <p class="help">Provide your website analytics tracking code here</p>
        </div>

        <br>

        <div class="field">
          <div class="control">
            <label class="label">Event Page Embed</label>
            <textarea class="input" name="event_page_embed" style="height:8em; font-family:courier; font-size: 0.8em;">{{ Setting::value('event_page_embed') }}</textarea>
            <p class="help">Provide some HTML or JS that will be embedded on the event permalinks. You can use this to add external comments to event pages for example, such as using the <a href="https://meta.discourse.org/t/embedding-discourse-comments-via-javascript/31963">Discourse embed</a> code. The magic string <code>%EVENT_URL%</code> will be replaced with the full URL to the event page.</p>
          </div>
        </div>

        <br>

        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">Notification Endpoint</label>
                <input class="input" type="url" value="{{ Setting::value('notification_endpoint') }}" name="notification_endpoint" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">Notification Token</label>
                <input class="input" type="password" value="{{ Setting::value('notification_token') ? '********' : '' }}" name="notification_token" autocomplete="off">
            </div>
        </div>
        <p class="help">When set, notifications about new and updated events will be sent to this URL with the token in the Authorization header and the notification text in a form post parameter named "content".</p>

        <br>

        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">Mail From Address</label>
                <input class="input" type="email" value="{{ Setting::value('mail_from_address') }}" name="mail_from_address" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">Mailgun Domain</label>
                <input class="input" type="text" value="{{ Setting::value('mailgun_domain') }}" name="mailgun_domain" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">Mailgun Secret</label>
                <input class="input" type="password" value="{{ Setting::value('mailgun_secret') ? '********' : '' }}" name="mailgun_secret" autocomplete="off">
            </div>
        </div>
        <p class="help">Configuring Mailgun enables this website to send email notifications for events that require registration.</p>

        <br>

        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">Zoom Email</label>
                <input class="input" type="email" value="{{ Setting::value('zoom_email') }}" name="zoom_email" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">Zoom API Key</label>
                <input class="input" type="password" value="{{ Setting::value('zoom_api_key') ? '********' : '' }}" name="zoom_api_key" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">Zoom API Secret</label>
                <input class="input" type="password" value="{{ Setting::value('zoom_api_secret') ? '********' : '' }}" name="zoom_api_secret" autocomplete="off">
            </div>
        </div>
        <p class="help">Create a <a href="https://marketplace.zoom.us/docs/guides/build/jwt-app">JWT Zoom Application</a> and enter the credentials above to give people the option of scheduling a Zoom meeting when creating an event. Enter the email address of the Zoom account you want to use to schedule the meetings.</p>

    </div>

    <br><br>
    <button class="button is-primary" type="submit">Save All Settings</button>



    {{ csrf_field() }}

</form>

</section>
@endsection
