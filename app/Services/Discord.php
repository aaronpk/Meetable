<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Event, App\Setting, App\DiscordNotification;
use Cache;

class Discord {

    const API = 'https://discord.com/api/v10';

    // View Channel + Send Messages + Embed Links
    const BOT_PERMISSIONS = 1024 | 2048 | 16384;

    const CHANNEL_TYPE_TEXT = 0;
    const CHANNEL_TYPE_CATEGORY = 4;
    const CHANNEL_TYPE_ANNOUNCEMENT = 5;

    const ERROR_UNKNOWN_GUILD = 10004;
    const ERROR_MISSING_ACCESS = 50001;
    const ERROR_MISSING_PERMISSIONS = 50013;

    const PERMISSION_ADMINISTRATOR = 8;
    const PERMISSION_VIEW_CHANNEL = 1024;
    const PERMISSION_SEND_MESSAGES = 2048;
    const PERMISSION_EMBED_LINKS = 16384;

    public static function enabled() {
        return env('AUTH_METHOD') == 'discord';
    }

    public static function botConfigured() {
        return (bool)env('DISCORD_BOT_TOKEN');
    }

    public static function guildId() {
        return env('DISCORD_SERVER_ID') ?: Setting::value('discord_guild_id');
    }

    public static function installUrl($state) {
        $params = [
            'client_id' => env('DISCORD_CLIENT_ID'),
            'scope' => 'bot',
            'permissions' => self::BOT_PERMISSIONS,
            'response_type' => 'code',
            'redirect_uri' => route('discord-install-callback'),
            'state' => $state,
        ];

        if($guild_id = self::guildId()) {
            $params['guild_id'] = $guild_id;
            $params['disable_guild_select'] = 'true';
        }

        return 'https://discord.com/oauth2/authorize?' . http_build_query($params);
    }

    private static function request() {
        return Http::withHeaders([
                'Authorization' => 'Bot '.env('DISCORD_BOT_TOKEN'),
                // Discord rejects bot requests that don't use this format with "internal network error" (40333)
                'User-Agent' => 'DiscordBot ('.env('APP_URL').', 1.0)',
            ])
            ->acceptJson()
            ->timeout(15);
    }

    private static function errorMessage($response) {
        $message = $response->json('message') ?: $response->body();
        return __('discord.api_error', ['status' => $response->status(), 'message' => $message]);
    }

    // Returns the guild if the bot is a member of it, or null if not
    public static function getGuild($guild_id=null) {
        $guild_id = $guild_id ?: self::guildId();

        if(!self::botConfigured() || !$guild_id)
            return null;

        $response = self::request()->get(self::API.'/guilds/'.$guild_id);

        if($response->successful())
            return $response->json();

        // Missing Access or Unknown Guild mean the bot hasn't been added to this server
        if(in_array($response->json('code'), [self::ERROR_MISSING_ACCESS, self::ERROR_UNKNOWN_GUILD]))
            return null;

        throw new DiscordException(self::errorMessage($response));
    }

    private static function get($path) {
        $response = self::request()->get(self::API.$path);

        if(!$response->successful())
            throw new DiscordException(self::errorMessage($response));

        return $response->json();
    }

    public static function botUser() {
        return Cache::remember('discord-bot-user', 3600, function(){
            return self::get('/users/@me');
        });
    }

    // Returns the text and announcement channels, sorted the way Discord displays them:
    // [['id' => ..., 'name' => ..., 'category' => ..., 'can_post' => bool], ...]
    // Discord lists every channel to the bot, including private channels it can't see.
    public static function getChannels() {
        $guild_id = self::guildId();

        if(!self::botConfigured() || !$guild_id)
            return [];

        return Cache::remember('discord-channels-'.$guild_id, 60, function() use($guild_id){
            $all = self::get('/guilds/'.$guild_id.'/channels');
            $roles = self::get('/guilds/'.$guild_id.'/roles');
            $member = self::get('/guilds/'.$guild_id.'/members/'.self::botUser()['id']);

            $categories = [];
            foreach($all as $c) {
                if($c['type'] == self::CHANNEL_TYPE_CATEGORY)
                    $categories[$c['id']] = $c;
            }

            $channels = [];
            foreach($all as $c) {
                if(!in_array($c['type'], [self::CHANNEL_TYPE_TEXT, self::CHANNEL_TYPE_ANNOUNCEMENT]))
                    continue;

                $category = isset($c['parent_id']) ? ($categories[$c['parent_id']] ?? null) : null;

                $channels[] = [
                    'id' => $c['id'],
                    'name' => $c['name'],
                    'category' => $category ? $category['name'] : '',
                    'can_post' => self::canPost(self::channelPermissions($guild_id, $roles, $member, $c)),
                    // Channels without a category are listed first, then by category position, then channel position
                    'sort' => [$category ? 1 : 0, $category['position'] ?? 0, $c['position'] ?? 0],
                ];
            }

            usort($channels, function($a, $b){
                return $a['sort'] <=> $b['sort'];
            });

            return array_map(function($c){
                unset($c['sort']);
                return $c;
            }, $channels);
        });
    }

    public static function canPost($permissions) {
        $required = self::BOT_PERMISSIONS;
        return ($permissions & $required) == $required;
    }

    // Computes a member's permissions in a channel from the server roles and the channel's overwrites
    // https://discord.com/developers/docs/topics/permissions#permission-overwrites
    public static function channelPermissions($guild_id, array $roles, array $member, array $channel) {
        $role_permissions = [];
        foreach($roles as $role)
            $role_permissions[$role['id']] = (int)$role['permissions'];

        // The @everyone role has the same ID as the server
        $permissions = $role_permissions[$guild_id] ?? 0;
        foreach($member['roles'] as $role_id)
            $permissions |= $role_permissions[$role_id] ?? 0;

        if($permissions & self::PERMISSION_ADMINISTRATOR)
            return PHP_INT_MAX;

        $overwrites = [];
        foreach($channel['permission_overwrites'] ?? [] as $o)
            $overwrites[$o['id']] = $o;

        if(isset($overwrites[$guild_id])) {
            $permissions &= ~(int)$overwrites[$guild_id]['deny'];
            $permissions |= (int)$overwrites[$guild_id]['allow'];
        }

        $allow = 0;
        $deny = 0;
        foreach($member['roles'] as $role_id) {
            if(isset($overwrites[$role_id])) {
                $allow |= (int)$overwrites[$role_id]['allow'];
                $deny |= (int)$overwrites[$role_id]['deny'];
            }
        }
        $permissions &= ~$deny;
        $permissions |= $allow;

        $user_id = $member['user']['id'] ?? null;
        if($user_id && isset($overwrites[$user_id])) {
            $permissions &= ~(int)$overwrites[$user_id]['deny'];
            $permissions |= (int)$overwrites[$user_id]['allow'];
        }

        return $permissions;
    }

    public static function channelAccessHelp() {
        try {
            $name = self::botUser()['username'];
        } catch(DiscordException $e) {
            $name = __('discord.the_bot');
        }
        return __('discord.access_help', ['bot' => $name]);
    }

    public static function forgetChannels() {
        Cache::forget('discord-channels-'.self::guildId());
    }

    // Posts a message and returns the Discord message ID
    public static function sendMessage($channel_id, array $payload) {
        if(!self::botConfigured())
            throw new DiscordException(__('discord.token_not_configured'));

        $url = self::API.'/channels/'.$channel_id.'/messages';

        $response = self::request()->post($url, $payload);

        if($response->status() == 429) {
            $retry_after = (float)($response->json('retry_after') ?? 1);
            usleep((int)(min($retry_after, 10) * 1000000));
            $response = self::request()->post($url, $payload);
        }

        if(!$response->successful()) {
            $message = self::errorMessage($response);
            if(in_array($response->json('code'), [self::ERROR_MISSING_ACCESS, self::ERROR_MISSING_PERMISSIONS]))
                $message .= '. '.self::channelAccessHelp();
            throw new DiscordException($message);
        }

        return $response->json('id');
    }

    public static function buildEventMessage(DiscordNotification $notification, Event $event) {
        $fields = [];

        $start = $event->start_datetime();
        if($event->start_time) {
            $when = '<t:'.$start->format('U').':F> (<t:'.$start->format('U').':R>)';
        } else {
            $when = \App\Helpers\Dates::format($start, 'date_full');
        }
        $fields[] = ['name' => __('discord.embed.when'), 'value' => $when, 'inline' => false];

        if($event->has_physical_location()) {
            $where = implode(', ', array_filter([
                $event->location_name,
                $event->location_locality,
                $event->location_region,
            ]));
            if($where)
                $fields[] = ['name' => __('discord.embed.where'), 'value' => Str::limit($where, 1000), 'inline' => false];
        }

        if($event->meeting_url) {
            $fields[] = ['name' => __('discord.embed.join'), 'value' => Str::limit($event->meeting_url, 1000), 'inline' => false];
        }

        if($event->status && $event->status != 'confirmed') {
            $fields[] = ['name' => __('discord.embed.status'), 'value' => Event::status_label($event->status), 'inline' => false];
        }

        $embed = [
            'title' => Str::limit($event->name, 250),
            'url' => $event->absolute_permalink(),
            'fields' => $fields,
        ];

        if($event->summary)
            $embed['description'] = Str::limit($event->summary, 4000);

        return [
            'content' => Str::limit((string)$notification->message, 2000),
            'embeds' => [$embed],
            // Allow user and role mentions in the custom message, but not @everyone/@here
            'allowed_mentions' => [
                'parse' => ['users', 'roles'],
            ],
        ];
    }

}
