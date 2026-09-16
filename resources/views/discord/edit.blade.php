@extends('layouts/main')

@php
list($time_before, $time_unit) = $notification->timeBeforeParts();
$time_before = old('time_before', $time_before);
$time_unit = old('time_unit', $time_unit);
$channel_id = old('channel_id', $notification->channel_id);
@endphp

@section('content')
<section class="section">

<h2 class="title">{{ $notification->id ? __('discord.form.edit_title') : __('discord.form.add_title') }}</h2>

@if($form_errors = session('discord-errors'))
    <div class="notification is-danger">
        @foreach($form_errors as $e)
            {{ $e }}<br>
        @endforeach
    </div>
@endif
@if($error)
    <div class="notification is-danger">{{ __('discord.form.channels_failed') }} {{ $error }}</div>
@endif

<form action="{{ route('save-discord-notification') }}" method="post">

    <div class="field">
        <label class="label" for="tag">{{ __('discord.form.tag') }}</label>
        <div class="control">
            <input class="input" type="text" name="tag" id="tag" list="tag-list" required autocomplete="off" value="{{ old('tag', $notification->tag) }}">
            <datalist id="tag-list">
                @foreach($tags as $tag)
                    <option value="{{ $tag }}">
                @endforeach
            </datalist>
        </div>
        <p class="help">{{ __('discord.form.tag_help') }}</p>
    </div>

    <div class="field">
        <label class="label" for="channel_id">{{ __('discord.form.channel') }}</label>
        <div class="control">
            <div class="select">
                <select name="channel_id" id="channel_id" required>
                    <option value="">{{ __('discord.form.choose_channel') }}</option>
                    @php $current_category = null; @endphp
                    @foreach($channels as $channel)
                        @if($channel['category'] !== $current_category)
                            @if($current_category !== null)</optgroup>@endif
                            <optgroup label="{{ $channel['category'] ?: __('discord.form.no_category') }}">
                            @php $current_category = $channel['category']; @endphp
                        @endif
                        <option value="{{ $channel['id'] }}" {{ $channel['id'] === $channel_id ? 'selected' : '' }}>#{{ $channel['name'] }}{{ $channel['can_post'] ? '' : ' '.__('discord.form.bot_needs_access_option') }}</option>
                    @endforeach
                    @if($current_category !== null)</optgroup>@endif
                </select>
            </div>
        </div>
        @if(collect($channels)->contains('can_post', false))
            <p class="help">{{ __('discord.form.private_channels_help') }} {{ App\Services\Discord::channelAccessHelp() }}</p>
        @endif
    </div>

    <div class="field">
        <label class="label" for="time_before">{{ __('discord.form.time_before') }}</label>
        <div class="field has-addons">
            <div class="control">
                <input class="input" type="number" name="time_before" id="time_before" min="1" step="1" required value="{{ $time_before }}" style="width: 8em;">
            </div>
            <div class="control">
                <div class="select">
                    <select name="time_unit" aria-label="{{ __('discord.form.unit') }}">
                        @foreach(App\DiscordNotification::$TIME_UNITS as $unit => $minutes)
                            <option value="{{ $unit }}" {{ $unit == $time_unit ? 'selected' : '' }}>{{ __('discord.units.'.$unit) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <p class="help">{{ __('discord.form.time_before_help') }}</p>
    </div>

    <div class="field">
        <label class="label" for="message">{{ __('discord.form.message') }}</label>
        <div class="control">
            <textarea class="textarea" name="message" id="message" rows="4" maxlength="2000">{{ old('message', $notification->message) }}</textarea>
        </div>
        <p class="help">{{ __('discord.form.message_help') }}
            {!! __('discord.form.mentions_help', ['role_mention' => '<code>&lt;@&amp;role-id&gt;</code>']) !!}</p>
    </div>

    <div class="field">
        <label class="checkbox">
            <input type="checkbox" name="enabled" value="1" {{ (old() ? old('enabled') : $notification->enabled) ? 'checked' : '' }}>
            {{ __('discord.form.enabled') }}
        </label>
    </div>

    <div class="field is-grouped">
        <div class="control">
            <button type="submit" class="button is-primary">{{ __('common.save') }}</button>
        </div>
        <div class="control">
            <a href="{{ route('discord-notifications') }}" class="button is-light">{{ __('common.cancel') }}</a>
        </div>
    </div>

    @if($notification->id)
        <input type="hidden" name="id" value="{{ $notification->id }}">
    @endif
    {{ csrf_field() }}
</form>

</section>
@endsection
