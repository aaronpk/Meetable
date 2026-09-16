@extends('layouts/main')

@php
list($time_before, $time_unit) = $notification->timeBeforeParts();
$time_before = old('time_before', $time_before);
$time_unit = old('time_unit', $time_unit);
$channel_id = old('channel_id', $notification->channel_id);
@endphp

@section('content')
<section class="section">

<h2 class="title">{{ $notification->id ? 'Edit' : 'Add' }} Discord Notification</h2>

@if($form_errors = session('discord-errors'))
    <div class="notification is-danger">
        @foreach($form_errors as $e)
            {{ $e }}<br>
        @endforeach
    </div>
@endif
@if($error)
    <div class="notification is-danger">Could not load the list of channels from Discord. {{ $error }}</div>
@endif

<form action="{{ route('save-discord-notification') }}" method="post">

    <div class="field">
        <label class="label" for="tag">Event Tag</label>
        <div class="control">
            <input class="input" type="text" name="tag" id="tag" list="tag-list" required autocomplete="off" value="{{ old('tag', $notification->tag) }}">
            <datalist id="tag-list">
                @foreach($tags as $tag)
                    <option value="{{ $tag }}">
                @endforeach
            </datalist>
        </div>
        <p class="help">A message will be posted for upcoming events that have this tag.</p>
    </div>

    <div class="field">
        <label class="label" for="channel_id">Discord Channel</label>
        <div class="control">
            <div class="select">
                <select name="channel_id" id="channel_id" required>
                    <option value="">Choose a channel</option>
                    @php $current_category = null; @endphp
                    @foreach($channels as $channel)
                        @if($channel['category'] !== $current_category)
                            @if($current_category !== null)</optgroup>@endif
                            <optgroup label="{{ $channel['category'] ?: 'No category' }}">
                            @php $current_category = $channel['category']; @endphp
                        @endif
                        <option value="{{ $channel['id'] }}" {{ $channel['id'] === $channel_id ? 'selected' : '' }}>#{{ $channel['name'] }}{{ $channel['can_post'] ? '' : ' (bot needs access)' }}</option>
                    @endforeach
                    @if($current_category !== null)</optgroup>@endif
                </select>
            </div>
        </div>
        @if(collect($channels)->contains('can_post', false))
            <p class="help">Channels marked "bot needs access" are private to the bot. {{ App\Services\Discord::channelAccessHelp() }}</p>
        @endif
    </div>

    <div class="field">
        <label class="label" for="time_before">Time Before the Event</label>
        <div class="field has-addons">
            <div class="control">
                <input class="input" type="number" name="time_before" id="time_before" min="1" step="1" required value="{{ $time_before }}" style="width: 8em;">
            </div>
            <div class="control">
                <div class="select">
                    <select name="time_unit" aria-label="Unit">
                        @foreach(App\DiscordNotification::$TIME_UNITS as $unit => $minutes)
                            <option value="{{ $unit }}" {{ $unit == $time_unit ? 'selected' : '' }}>{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <p class="help">How long before the event starts to post the message, up to 30 days. Events without a start time are treated as starting at midnight.</p>
    </div>

    <div class="field">
        <label class="label" for="message">Message</label>
        <div class="control">
            <textarea class="textarea" name="message" id="message" rows="4" maxlength="2000">{{ old('message', $notification->message) }}</textarea>
        </div>
        <p class="help">This text is posted along with the event's name, link, time, location and meeting link.
            You can use Discord formatting, and mention a role with <code>&lt;@&amp;role-id&gt;</code>. Mentioning @everyone or @here is disabled.</p>
    </div>

    <div class="field">
        <label class="checkbox">
            <input type="checkbox" name="enabled" value="1" {{ (old() ? old('enabled') : $notification->enabled) ? 'checked' : '' }}>
            Enabled
        </label>
    </div>

    <div class="field is-grouped">
        <div class="control">
            <button type="submit" class="button is-primary">Save</button>
        </div>
        <div class="control">
            <a href="{{ route('discord-notifications') }}" class="button is-light">Cancel</a>
        </div>
    </div>

    @if($notification->id)
        <input type="hidden" name="id" value="{{ $notification->id }}">
    @endif
    {{ csrf_field() }}
</form>

</section>
@endsection
