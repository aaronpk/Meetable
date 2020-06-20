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


    <h2 class="title">Event Settings</h2>

    <div class="field">
        <label class="label">
            <input type="checkbox" name="enable_ticket_url" value="1" {{ Setting::value('enable_ticket_url') ? 'checked="checked"' : ''}}>
            Enable Registration URL
        </label>
        <p class="help">Show or hide the "Registration URL" field on events.</p>
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




    <br><br>
    <h2 class="title">Custom Messages</h2>

    <div class="field">
        <label class="label">Add an Event</label>
        <textarea class="input" name="add_an_event" style="max-height: none; height: 25vh">{{ Setting::value('add_an_event') }}</textarea>
        <div class="help">You can edit the text that appears on the "Add an Event" page for users. Use this to describe what kinds of events should be added to the website. Markdown and HTML are supported.</div>
    </div>




    <br><br>
    <h2 class="title">Design</h2>

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


    <br><br>
    <h2 class="title">Services</h2>

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
        <textarea class="input" name="analytics" style="height:6em">{{ Setting::value('analytics') }}</textarea>
      </div>
      <p class="help">Provide your website analytics tracking code here</p>
    </div>

    <br>

    <button class="button is-primary" type="submit">Save All Settings</button>

    <br><br>

    {{ csrf_field() }}

</form>

</section>
@endsection
