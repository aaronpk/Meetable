@extends('layouts/main')

@section('content')
<section class="section content">

<h2 class="title">{{ __('discord.title') }}</h2>

@if($message = session('discord-success'))
    <div class="notification is-primary">{{ $message }}</div>
@endif
@if($message = session('discord-error'))
    <div class="notification is-danger">{{ $message }}</div>
@endif
@if($error)
    <div class="notification is-danger">{{ $error }}</div>
@endif

<p>{{ __('discord.intro') }}</p>

<div class="box">
    @if(!$bot_configured)
        <p><strong>{{ __('discord.setup.not_configured') }}</strong></p>
        <p>{{ __('discord.setup.steps_intro') }}</p>
        <ol>
            <li>{!! __('discord.setup.step_token', [
                'bot_settings' => '<a href="https://discord.com/developers/applications/'.e(env('DISCORD_CLIENT_ID')).'/bot">'.e(__('discord.setup.bot_settings')).'</a>',
                'env_file' => '<code>.env</code>',
                'variable' => '<code>DISCORD_BOT_TOKEN</code>',
            ]) !!}</li>
            <li>{!! __('discord.setup.step_redirect', [
                'url' => '<code>'.e(route('discord-install-callback')).'</code>',
                'oauth_settings' => '<a href="https://discord.com/developers/applications/'.e(env('DISCORD_CLIENT_ID')).'/oauth2">'.e(__('discord.setup.oauth_settings')).'</a>',
            ]) !!}</li>
        </ol>
    @elseif(!$guild)
        <p><strong>{{ __('discord.setup.not_installed') }}</strong></p>
        <p>{{ __('discord.setup.needs_admin') }}</p>
        <a href="{{ route('discord-install') }}" class="button is-primary">{{ __('discord.setup.add_bot') }}</a>
    @else
        <p>{!! __('discord.setup.installed_in', ['server' => '<strong>'.e($guild['name']).'</strong>']) !!}</p>
        <p class="help">{{ __('discord.setup.check_permissions') }}
            {!! __('discord.setup.reinstall', ['add_it_again' => '<a href="'.e(route('discord-install')).'">'.e(__('discord.setup.add_it_again')).'</a>']) !!}</p>
    @endif
</div>

@if($bot_configured && $guild)
    <p><a href="{{ route('new-discord-notification') }}" class="button is-primary">{{ __('discord.add_notification') }}</a></p>
@endif

@if(collect($channels)->contains('can_post', false) && $notifications->contains(fn($n) => isset($channels[$n->channel_id]) && !$channels[$n->channel_id]['can_post']))
    <div class="notification is-warning">{{ __('discord.cant_post_in_some') }} {{ App\Services\Discord::channelAccessHelp() }}</div>
@endif

@if(count($notifications))
<div class="table-container">
<table class="table is-fullwidth">
    <thead>
        <tr>
            <th>{{ __('discord.columns.tag') }}</th>
            <th>{{ __('discord.columns.channel') }}</th>
            <th>{{ __('discord.columns.when') }}</th>
            <th>{{ __('discord.columns.message') }}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    @foreach($notifications as $notification)
        <tr>
            <td><a href="{{ route('tag', $notification->tag) }}">#{{ $notification->tag }}</a></td>
            <td>
                #{{ $notification->channel_name }}
                @if(isset($channels[$notification->channel_id]) && !$channels[$notification->channel_id]['can_post'])
                    <br><span class="tag is-danger is-light" title="{{ App\Services\Discord::channelAccessHelp() }}">{{ __('discord.bot_needs_access') }}</span>
                @elseif($channels && !isset($channels[$notification->channel_id]))
                    <br><span class="tag is-danger is-light">{{ __('discord.channel_not_found') }}</span>
                @endif
            </td>
            <td>
                {{ __('discord.time_before', ['time' => $notification->timeBeforeText()]) }}
                @if(!$notification->enabled)
                    <br><span class="tag is-warning">{{ __('discord.disabled') }}</span>
                @endif
            </td>
            <td>{{ Str::limit($notification->message, 100) }}</td>
            <td>
                <div class="buttons are-small" style="flex-wrap: nowrap;">
                    <a href="{{ route('edit-discord-notification', $notification) }}" class="button">{{ __('common.edit') }}</a>
                    <form action="{{ route('test-discord-notification', $notification) }}" method="post">
                        {{ csrf_field() }}
                        <button type="submit" class="button">{{ __('discord.send_test') }}</button>
                    </form>
                    <form action="{{ route('delete-discord-notification', $notification) }}" method="post" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('discord.delete_confirm')) }})">
                        {{ csrf_field() }}
                        <button type="submit" class="button is-danger is-light">{{ __('common.delete') }}</button>
                    </form>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@elseif($bot_configured && $guild)
    <p>{{ __('discord.none_yet') }}</p>
@endif

</section>
@endsection
