@extends('layouts/main')

@section('content')
<section class="section content">

<h2 class="title">Discord Notifications</h2>

@if($message = session('discord-success'))
    <div class="notification is-primary">{{ $message }}</div>
@endif
@if($message = session('discord-error'))
    <div class="notification is-danger">{{ $message }}</div>
@endif
@if($error)
    <div class="notification is-danger">{{ $error }}</div>
@endif

<p>Post a message to a Discord channel before events with a particular tag start.</p>

<div class="box">
    @if(!$bot_configured)
        <p><strong>The Discord bot is not configured yet.</strong></p>
        <p>To set it up, the site owner needs to:</p>
        <ol>
            <li>Open the <a href="https://discord.com/developers/applications/{{ env('DISCORD_CLIENT_ID') }}/bot">Bot settings</a> for this Discord application, reset the token, and add it to the <code>.env</code> file as <code>DISCORD_BOT_TOKEN</code></li>
            <li>Add <code>{{ route('discord-install-callback') }}</code> as a redirect URL in the application's <a href="https://discord.com/developers/applications/{{ env('DISCORD_CLIENT_ID') }}/oauth2">OAuth2 settings</a></li>
        </ol>
    @elseif(!$guild)
        <p><strong>The bot has not been added to the Discord server yet.</strong></p>
        <p>Someone with the "Manage Server" permission in Discord needs to add the bot so it can post to channels.</p>
        <a href="{{ route('discord-install') }}" class="button is-primary">Add the bot to Discord</a>
    @else
        <p>The bot is installed in <strong>{{ $guild['name'] }}</strong>.</p>
        <p class="help">Make sure the bot can view and send messages in the channels you choose. Use the "Send Test" button to check.
            If the bot was removed from the server, <a href="{{ route('discord-install') }}">add it again</a>.</p>
    @endif
</div>

@if($bot_configured && $guild)
    <p><a href="{{ route('new-discord-notification') }}" class="button is-primary">Add Notification</a></p>
@endif

@if(collect($channels)->contains('can_post', false) && $notifications->contains(fn($n) => isset($channels[$n->channel_id]) && !$channels[$n->channel_id]['can_post']))
    <div class="notification is-warning">The bot can't post in some of these channels. {{ App\Services\Discord::channelAccessHelp() }}</div>
@endif

@if(count($notifications))
<div class="table-container">
<table class="table is-fullwidth">
    <thead>
        <tr>
            <th>Tag</th>
            <th>Channel</th>
            <th>When</th>
            <th>Message</th>
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
                    <br><span class="tag is-danger is-light" title="{{ App\Services\Discord::channelAccessHelp() }}">Bot needs access</span>
                @elseif($channels && !isset($channels[$notification->channel_id]))
                    <br><span class="tag is-danger is-light">Channel not found</span>
                @endif
            </td>
            <td>
                {{ $notification->timeBeforeText() }} before
                @if(!$notification->enabled)
                    <br><span class="tag is-warning">Disabled</span>
                @endif
            </td>
            <td>{{ Str::limit($notification->message, 100) }}</td>
            <td>
                <div class="buttons are-small" style="flex-wrap: nowrap;">
                    <a href="{{ route('edit-discord-notification', $notification) }}" class="button">Edit</a>
                    <form action="{{ route('test-discord-notification', $notification) }}" method="post">
                        {{ csrf_field() }}
                        <button type="submit" class="button">Send Test</button>
                    </form>
                    <form action="{{ route('delete-discord-notification', $notification) }}" method="post" onsubmit="return confirm('Delete this notification?')">
                        {{ csrf_field() }}
                        <button type="submit" class="button is-danger is-light">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@elseif($bot_configured && $guild)
    <p>No notifications have been set up yet.</p>
@endif

</section>
@endsection
