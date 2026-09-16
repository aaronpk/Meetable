<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\DiscordNotification, App\Event, App\Tag, App\Setting;
use App\Services\Discord, App\Services\DiscordException;
use App\Helpers\Locales;
use DateTime, DateInterval;
use Auth, Gate, Log;

class DiscordNotificationController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // Any logged-in user can manage Discord notifications, but only on sites that use Discord login
    private function authorizeDiscord() {
        Gate::authorize('logged-in');

        if(!Discord::enabled())
            abort(404);
    }

    public function index() {
        $this->authorizeDiscord();

        $guild = null;
        $channels = [];
        $error = null;
        try {
            $guild = Discord::getGuild();
            if($guild)
                $channels = collect(Discord::getChannels())->keyBy('id')->all();
        } catch(DiscordException $e) {
            $error = $e->getMessage();
        }

        return view('discord/index', [
            'bot_configured' => Discord::botConfigured(),
            'guild' => $guild,
            'channels' => $channels,
            'error' => $error,
            'notifications' => DiscordNotification::orderBy('tag')->orderBy('minutes_before', 'desc')->get(),
        ]);
    }

    public function install() {
        $this->authorizeDiscord();

        if(!Discord::botConfigured())
            return redirect(route('discord-notifications'));

        $state = bin2hex(random_bytes(16));
        session(['DISCORD_INSTALL_STATE' => $state]);

        return redirect(Discord::installUrl($state));
    }

    public function install_callback() {
        $this->authorizeDiscord();

        $expected_state = session()->pull('DISCORD_INSTALL_STATE');
        if(!is_string($expected_state) || !is_string(request('state')) || !hash_equals($expected_state, request('state'))) {
            session()->flash('discord-error', __('discord.messages.install_not_verified'));
            return redirect(route('discord-notifications'));
        }

        if(request('error')) {
            session()->flash('discord-error', __('discord.messages.not_installed', ['error' => request('error_description') ?: request('error')]));
            return redirect(route('discord-notifications'));
        }

        $guild_id = request('guild_id');

        // Without a configured server, use the one the bot was added to, once the bot is confirmed to be in it
        if(!env('DISCORD_SERVER_ID') && is_string($guild_id) && preg_match('/^\d+$/', $guild_id))
            $check_guild_id = $guild_id;
        else
            $check_guild_id = Discord::guildId();

        try {
            $guild = Discord::getGuild($check_guild_id);
        } catch(DiscordException $e) {
            session()->flash('discord-error', $e->getMessage());
            return redirect(route('discord-notifications'));
        }

        if($guild && !env('DISCORD_SERVER_ID'))
            Setting::set('discord_guild_id', $guild['id']);

        if($guild) {
            Discord::forgetChannels();
            session()->flash('discord-success', __('discord.messages.installed', ['server' => $guild['name']]));
        } else {
            session()->flash('discord-error', __('discord.messages.not_in_server'));
        }

        return redirect(route('discord-notifications'));
    }

    public function new_notification() {
        $this->authorizeDiscord();

        $notification = new DiscordNotification;
        $notification->minutes_before = 15;
        $notification->enabled = true;

        return $this->edit_form($notification);
    }

    public function edit_notification(DiscordNotification $notification) {
        $this->authorizeDiscord();

        return $this->edit_form($notification);
    }

    private function edit_form(DiscordNotification $notification) {
        $channels = [];
        $error = null;
        try {
            $channels = Discord::getChannels();
        } catch(DiscordException $e) {
            $error = $e->getMessage();
        }

        return view('discord/edit', [
            'notification' => $notification,
            'channels' => $channels,
            'error' => $error,
            'tags' => Tag::orderBy('tag')->pluck('tag'),
        ]);
    }

    public function save_notification() {
        $this->authorizeDiscord();

        if(request('id')) {
            $notification = DiscordNotification::findOrFail(request('id'));
            $back = route('edit-discord-notification', $notification);
        } else {
            $notification = new DiscordNotification;
            $notification->created_by = Auth::user()->id;
            $back = route('new-discord-notification');
        }

        $errors = [];

        $tag = Tag::normalize((string)request('tag'));
        if(!$tag)
            $errors[] = __('discord.messages.enter_tag');

        $unit = request('time_unit');
        $number = request('time_before');
        if(!isset(DiscordNotification::$TIME_UNITS[$unit]) || !is_numeric($number) || (int)$number != $number) {
            $errors[] = __('discord.messages.whole_number');
            $minutes_before = 0;
        } else {
            $minutes_before = (int)$number * DiscordNotification::$TIME_UNITS[$unit];
            if($minutes_before < 1 || $minutes_before > DiscordNotification::MAX_MINUTES_BEFORE)
                $errors[] = __('discord.messages.time_range');
        }

        if(mb_strlen((string)request('message')) > 2000)
            $errors[] = __('discord.messages.message_too_long');

        $channel = null;
        try {
            // Always check the channel against a fresh list from Discord
            Discord::forgetChannels();
            foreach(Discord::getChannels() as $c) {
                if($c['id'] === request('channel_id'))
                    $channel = $c;
            }
            if(!$channel)
                $errors[] = __('discord.messages.choose_channel');
        } catch(DiscordException $e) {
            $errors[] = $e->getMessage();
        }

        if($errors) {
            session()->flash('discord-errors', $errors);
            return redirect($back)->withInput();
        }

        $notification->tag = $tag;
        $notification->channel_id = $channel['id'];
        $notification->channel_name = $channel['name'];
        $notification->minutes_before = $minutes_before;
        $notification->message = request('message');
        $notification->enabled = request('enabled') ? 1 : 0;
        $notification->last_modified_by = Auth::user()->id;
        $notification->save();

        session()->flash('discord-success', __('discord.messages.saved'));
        return redirect(route('discord-notifications'));
    }

    public function delete_notification(DiscordNotification $notification) {
        $this->authorizeDiscord();

        $notification->sends()->delete();
        $notification->delete();

        session()->flash('discord-success', __('discord.messages.deleted'));
        return redirect(route('discord-notifications'));
    }

    public function test_notification(DiscordNotification $notification) {
        $this->authorizeDiscord();

        $event = $notification->nextEvent();

        if($event) {
            $payload = Discord::buildEventMessage($notification, $event);
            $description = __('discord.messages.test_using_event', ['name' => $event->name]);
        } else {
            // No upcoming event has this tag, so post an example of what the message will look like
            $start = new DateTime();
            $start->add(new DateInterval('PT'.$notification->minutes_before.'M'));

            $event = new Event;
            $event->name = __('discord.example.name', [], Locales::site());
            $event->start_date = $start->format('Y-m-d');
            $event->start_time = $start->format('H:i:s');
            $event->timezone = 'UTC';
            $event->status = 'confirmed';
            $event->summary = __('discord.example.summary', ['tag' => $notification->tag], Locales::site());

            $payload = Discord::buildEventMessage($notification, $event);
            $payload['embeds'][0]['url'] = env('APP_URL').'/tag/'.$notification->tag;
            $description = __('discord.messages.test_with_example');
        }

        try {
            Discord::sendMessage($notification->channel_id, $payload);
            session()->flash('discord-success', __('discord.messages.test_posted', ['channel' => $notification->channel_name, 'description' => $description]));
        } catch(DiscordException $e) {
            Log::error('Discord test notification failed: '.$e->getMessage());
            session()->flash('discord-error', __('discord.messages.test_failed', ['channel' => $notification->channel_name, 'error' => $e->getMessage()]));
        }

        return redirect(route('discord-notifications'));
    }

}
