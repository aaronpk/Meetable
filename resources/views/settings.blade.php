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
            <li class="is-active" data-tab="messages"><a>{{ __('settings.tabs.messages') }}</a></li>
            <li data-tab="design"><a>{{ __('settings.tabs.design') }}</a></li>
            <li data-tab="features"><a>{{ __('settings.tabs.features') }}</a></li>
            <li data-tab="services"><a>{{ __('settings.tabs.services') }}</a></li>
        </ul>
    </div>

    <div class="tab-content" id="tab-features">
        <div class="field">
            <label class="label">
                <input type="checkbox" name="enable_ticket_url" value="1" {{ Setting::value('enable_ticket_url') ? 'checked="checked"' : ''}}>
                {{ __('settings.enable_ticket_url') }}
            </label>
            <p class="help">{{ __('settings.enable_ticket_url_help') }}</p>
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
                {{ __('settings.enable_unlisted_events') }}
            </label>
            <p class="help">{{ __('settings.enable_unlisted_events_help') }}</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="enable_rsvps" value="1" {{ Setting::value('enable_rsvps') ? 'checked="checked"' : ''}}>
                {{ __('settings.enable_rsvps') }}
            </label>
            <p class="help">{{ __('settings.enable_rsvps_help') }}</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="show_rsvps_in_ics" value="1" {{ Setting::value('show_rsvps_in_ics') ? 'checked="checked"' : ''}}>
                {{ __('settings.show_rsvps_in_ics') }}
            </label>
            <p class="help">{{ __('settings.show_rsvps_in_ics_help') }}</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="enable_proposed_events" value="1" {{ Setting::value('enable_proposed_events') ? 'checked="checked"' : ''}}>
                {{ __('settings.enable_proposed_events') }}
            </label>
            <p class="help">{{ __('settings.enable_proposed_events_help') }}</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="show_meeting_url_in_ics" value="1" {{ Setting::value('show_meeting_url_in_ics') ? 'checked="checked"' : ''}}>
                {{ __('settings.show_meeting_url_in_ics') }}
            </label>
            <p class="help">{{ __('settings.show_meeting_url_in_ics_help') }}</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="clone_meeting_url" value="1" {{ Setting::value('clone_meeting_url') ? 'checked="checked"' : ''}}>
                {{ __('settings.clone_meeting_url') }}
            </label>
            <p class="help">{{ __('settings.clone_meeting_url_help') }}</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="enable_webmention_responses" value="1" {{ Setting::value('enable_webmention_responses') ? 'checked="checked"' : ''}}>
                {{ __('settings.enable_webmention_responses') }}
            </label>
            <p class="help">{!! __('settings.enable_webmention_responses_help', ['webmention' => '<a href="https://webmention.net">Webmention</a>']) !!}</p>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="auth_hide_login" value="1" {{ Setting::value('auth_hide_login') ? 'checked="checked"' : ''}}>
                {{ __('settings.auth_hide_login') }}
            </label>
        </div>

        <div class="field">
            <label class="label">
                <input type="checkbox" name="auth_hide_logout" value="1" {{ Setting::value('auth_hide_logout') ? 'checked="checked"' : ''}}>
                {{ __('settings.auth_hide_logout') }}
            </label>
        </div>

    </div>


    <div class="tab-content" id="tab-messages">
        <div class="field">
            <label class="label">{{ __('settings.add_an_event') }}</label>
            <textarea class="input" name="add_an_event" style="max-height: none; height: 25vh">{{ Setting::value('add_an_event') }}</textarea>
            <div class="help">{{ __('settings.add_an_event_help') }}</div>
        </div>

        <div class="field">
            <label class="label">{{ __('settings.photo_license') }}</label>
            <textarea class="input" name="photo_license" style="max-height: none; height: 25vh">{{ Setting::value('photo_license') }}</textarea>
            <div class="help">{{ __('settings.photo_license_help') }}</div>
        </div>

        <div class="field">
          <div class="control">
            <label class="label">{{ __('settings.default_coc_url') }}</label>
            <input class="input" type="url" value="{{ Setting::value('default_coc_url') }}" name="default_coc_url">
          </div>
          <p class="help">{{ __('settings.default_coc_url_help') }}</p>
        </div>
    </div>



    <div class="tab-content" id="tab-design">
        <div class="field">
          <div class="control">
            <label class="label">{{ __('settings.logo_url') }}</label>
            <input class="input" type="url" value="{{ Setting::value('logo_url') }}" name="logo_url">
          </div>
          <p class="help">{{ __('settings.logo_url_help') }}</p>
        </div>

        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">{{ __('settings.logo_width') }}</label>
                <input class="input" type="text" name="logo_width" autocomplete="off" value="{{ Setting::value('logo_width') }}">
            </div>

            <div class="control is-expanded">
                <label class="label">{{ __('settings.logo_height') }}</label>
                <input class="input" type="text" name="logo_height" autocomplete="off" value="{{ Setting::value('logo_height') }}">
            </div>
            <p class="help">{{ __('settings.logo_size_help') }}</p>
        </div>

        <div class="field">
            <div class="control">
                <label class="label">{{ __('settings.favicon_url') }}</label>
                <input class="input" type="url" value="{{ Setting::value('favicon_url') }}" name="favicon_url">
            </div>
            <p class="help">{{ __('settings.favicon_url_help') }}</p>
        </div>

        <div class="field">
            <div class="control">
                <label class="label">{{ __('settings.manifest_logo_url') }}</label>
                <input class="input" type="url" value="{{ Setting::value('manifest_logo_url') }}" name="manifest_logo_url">
            </div>
            <p class="help">{!! __('settings.manifest_logo_url_help', ['manifest' => '<code>manifest.json</code>']) !!}</p>
        </div>

        <div class="field">
          <div class="control">
            <label class="label">{{ __('settings.home_social_image_url') }}</label>
            <input class="input" type="url" value="{{ Setting::value('home_social_image_url') }}" name="home_social_image_url">
          </div>
          <p class="help">{{ __('settings.home_social_image_url_help') }}</p>
        </div>

        <div class="field">
          <div class="control">
            <label class="label">{{ __('settings.home_meta_description') }}</label>
            <input class="input" type="text" value="{{ Setting::value('home_meta_description') }}" name="home_meta_description">
          </div>
          <p class="help">{{ __('settings.home_meta_description_help') }}</p>
        </div>

        <div class="field">
          <div class="control">
            <label class="label">{{ __('settings.custom_global_css') }}</label>
            <textarea class="textarea" name="custom_global_css" rows="8">{{ Setting::value('custom_global_css') }}</textarea>
          </div>
          <p class="help">{{ __('settings.custom_global_css_help') }}</p>
        </div>


    </div>


    <div class="tab-content" id="tab-services">
        <div class="field">
          <div class="control">
            <label class="label">{{ __('settings.googlemaps_api_key') }}</label>
            <input class="input" type="password" value="{{ Setting::value('googlemaps_api_key') ? '********' : '' }}" name="googlemaps_api_key" autocomplete="off">
          </div>
          <p class="help">{!! __('settings.googlemaps_api_key_help', ['link' => '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key">'.e(__('settings.googlemaps_api_key_link')).'</a>']) !!}</p>
        </div>

        <br>

        <div class="field">
          <div class="control">
            <label class="label">{{ __('settings.analytics') }}</label>
            <textarea class="input" name="analytics" style="height:8em; font-family:courier; font-size: 0.8em;">{{ Setting::value('analytics') }}</textarea>
          </div>
          <p class="help">{{ __('settings.analytics_help') }}</p>
        </div>

        <br>

        <div class="field">
          <div class="control">
            <label class="label">{{ __('settings.event_page_embed') }}</label>
            <textarea class="input" name="event_page_embed" style="height:8em; font-family:courier; font-size: 0.8em;">{{ Setting::value('event_page_embed') }}</textarea>
            <p class="help">{!! __('settings.event_page_embed_help', ['link' => '<a href="https://meta.discourse.org/t/embedding-discourse-comments-via-javascript/31963">'.e(__('settings.event_page_embed_link')).'</a>', 'magic_string' => '<code>%EVENT_URL%</code>']) !!}</p>
          </div>
        </div>

        <br>

        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">{{ __('settings.notification_endpoint') }}</label>
                <input class="input" type="url" value="{{ Setting::value('notification_endpoint') }}" name="notification_endpoint" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">{{ __('settings.notification_token') }}</label>
                <input class="input" type="password" value="{{ Setting::value('notification_token') ? '********' : '' }}" name="notification_token" autocomplete="off">
            </div>
        </div>
        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">{{ __('settings.notification_channel_primary') }}</label>
                <input class="input" type="text" value="{{ Setting::value('notification_channel_primary') }}" name="notification_channel_primary" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">{{ __('settings.notification_channel_meta') }}</label>
                <input class="input" type="text" value="{{ Setting::value('notification_channel_meta') }}" name="notification_channel_meta" autocomplete="off">
            </div>
        </div>
        <p class="help">{{ __('settings.notifications_help') }}</p>

        <br>

        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">{{ __('settings.mail_from_address') }}</label>
                <input class="input" type="email" value="{{ Setting::value('mail_from_address') }}" name="mail_from_address" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">{{ __('settings.mailgun_domain') }}</label>
                <input class="input" type="text" value="{{ Setting::value('mailgun_domain') }}" name="mailgun_domain" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">{{ __('settings.mailgun_secret') }}</label>
                <input class="input" type="password" value="{{ Setting::value('mailgun_secret') ? '********' : '' }}" name="mailgun_secret" autocomplete="off">
            </div>
        </div>
        <p class="help">{{ __('settings.mailgun_help') }}</p>

        <br>

        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">{{ __('settings.zoom_email') }}</label>
                <input class="input" type="email" value="{{ Setting::value('zoom_email') }}" name="zoom_email" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">{{ __('settings.zoom_account_id') }}</label>
                <input class="input" type="text" value="{{ Setting::value('zoom_account_id') }}" name="zoom_account_id" autocomplete="off">
            </div>
        </div>
        <div class="field is-grouped is-grouped-multiline">
            <div class="control is-expanded">
                <label class="label">{{ __('settings.zoom_client_id') }}</label>
                <input class="input" type="text" value="{{ Setting::value('zoom_client_id') }}" name="zoom_client_id" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">{{ __('settings.zoom_client_secret') }}</label>
                <input class="input" type="password" value="{{ Setting::value('zoom_client_secret') ? '********' : '' }}" name="zoom_client_secret" autocomplete="off">
            </div>

            <div class="control is-expanded">
                <label class="label">{{ __('settings.zoom_webhook_secret') }}</label>
                <input class="input" type="password" value="{{ Setting::value('zoom_webhook_secret') ? '********' : '' }}" name="zoom_webhook_secret" autocomplete="off">
            </div>
        </div>
        <p class="help">{!! __('settings.zoom_help', ['link' => '<a href="https://developers.zoom.us/docs/internal-apps/s2s-oauth/">'.e(__('settings.zoom_link')).'</a>']) !!}</p>

    </div>

    <br><br>
    <button class="button is-primary" type="submit">{{ __('settings.save') }}</button>



    {{ csrf_field() }}

</form>

</section>
@endsection
