<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Event, App\Setting, App\DiscordNotification;
use Cache;

class Discord {

    const API = 'https://discord.com/api/v10';

    // View Channel + Send Messages + Embed Links: what the bot needs in a channel to post there
    const CHANNEL_PERMISSIONS = 1024 | 2048 | 16384;
    // Create Events to add events to the server's event list, and Manage Events to change
    // or remove them whoever made them
    const PERMISSION_MANAGE_EVENTS = 8589934592;
    const PERMISSION_CREATE_EVENTS = 17592186044416;
    // What the bot is installed with
    const BOT_PERMISSIONS = self::CHANNEL_PERMISSIONS | self::PERMISSION_CREATE_EVENTS | self::PERMISSION_MANAGE_EVENTS;

    const CHANNEL_TYPE_TEXT = 0;
    const CHANNEL_TYPE_CATEGORY = 4;
    const CHANNEL_TYPE_ANNOUNCEMENT = 5;

    const ERROR_UNKNOWN_GUILD = 10004;
    const ERROR_UNKNOWN_SCHEDULED_EVENT = 10070;
    const ERROR_MISSING_ACCESS = 50001;
    const ERROR_MISSING_PERMISSIONS = 50013;

    // Scheduled events held somewhere other than a voice or stage channel
    const SCHEDULED_EVENT_EXTERNAL = 3;
    const SCHEDULED_EVENT_PRIVACY_GUILD_ONLY = 2;
    const SCHEDULED_EVENT_STATUS_CANCELED = 4;
    const SCHEDULED_EVENT_MAX_IMAGE_BYTES = 2097152;

    const PERMISSION_ADMINISTRATOR = 8;
    const PERMISSION_VIEW_CHANNEL = 1024;
    const PERMISSION_SEND_MESSAGES = 2048;
    const PERMISSION_EMBED_LINKS = 16384;

    // The bot can be used with any login method once its token is set. A site that
    // logs in with Discord gets the setup page before that, to see how to set it up.
    public static function enabled() {
        return env('AUTH_METHOD') == 'discord' || self::botConfigured();
    }

    // On a site that logs in with Discord every user is a member of the server, so any of
    // them can set up the bot. Elsewhere that is for whoever can manage the site.
    public static function canManage($user) {
        if(!$user || !self::enabled())
            return false;

        if(env('AUTH_METHOD') == 'discord')
            return true;

        return \Gate::forUser($user)->allows('manage-site');
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
        $required = self::CHANNEL_PERMISSIONS;
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

    // Sends a request to Discord, waiting once if it was rate limited, and returns the response
    private static function send($method, $path, array $payload=[], $reason=null) {
        if(!self::botConfigured())
            throw new DiscordException(__('discord.token_not_configured'));

        $url = self::API.$path;

        $attempt = function() use($method, $url, $payload, $reason) {
            $request = self::request();
            // Shown in the server's audit log next to the change
            if($reason)
                $request = $request->withHeaders(['X-Audit-Log-Reason' => rawurlencode(Str::limit($reason, 500, ''))]);
            return $method == 'delete' ? $request->delete($url) : $request->{$method}($url, $payload);
        };

        $response = $attempt();

        if($response->status() == 429) {
            $retry_after = (float)($response->json('retry_after') ?? 1);
            usleep((int)(min($retry_after, 10) * 1000000));
            $response = $attempt();
        }

        return $response;
    }

    // Posts a message and returns the Discord message ID
    public static function sendMessage($channel_id, array $payload) {
        $response = self::send('post', '/channels/'.$channel_id.'/messages', $payload);

        if(!$response->successful()) {
            $message = self::errorMessage($response);
            if(in_array($response->json('code'), [self::ERROR_MISSING_ACCESS, self::ERROR_MISSING_PERMISSIONS]))
                $message .= '. '.self::channelAccessHelp();
            throw new DiscordException($message);
        }

        return $response->json('id');
    }

    // Posts are written in the site's language, whoever triggered them
    public static function buildEventMessage(DiscordNotification $notification, Event $event) {
        return \App\Helpers\Locales::inSiteLocale(function() use($notification, $event) {
            return self::eventMessage($notification, $event);
        });
    }

    private static function eventMessage(DiscordNotification $notification, Event $event) {
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

    /*
     * Scheduled events: the server's event list.
     * https://docs.discord.com/developers/resources/guild-scheduled-event
     */

    // Creates a scheduled event in the server and returns its ID
    public static function createScheduledEvent(array $payload, $reason=null) {
        $response = self::send('post', '/guilds/'.self::guildId().'/scheduled-events', $payload, $reason);
        return self::scheduledEventResponse($response)['id'];
    }

    public static function updateScheduledEvent($id, array $payload, $reason=null) {
        $response = self::send('patch', '/guilds/'.self::guildId().'/scheduled-events/'.$id, $payload, $reason);
        return self::scheduledEventResponse($response);
    }

    // A cancelled scheduled event stays in the list, marked cancelled, and can't be changed again
    public static function cancelScheduledEvent($id, $reason=null) {
        return self::updateScheduledEvent($id, ['status' => self::SCHEDULED_EVENT_STATUS_CANCELED], $reason);
    }

    public static function deleteScheduledEvent($id, $reason=null) {
        $response = self::send('delete', '/guilds/'.self::guildId().'/scheduled-events/'.$id, [], $reason);
        self::scheduledEventResponse($response);
    }

    // Returns the response body, or throws a DiscordException whose code is Discord's error code
    private static function scheduledEventResponse($response) {
        if($response->successful())
            return $response->json() ?: [];

        $code = (int)$response->json('code');
        // A plain 404 means the event is gone, the same as Discord's own code for that
        if($response->status() == 404 && !$code)
            $code = self::ERROR_UNKNOWN_SCHEDULED_EVENT;

        $message = self::errorMessage($response);
        if(in_array($code, [self::ERROR_MISSING_ACCESS, self::ERROR_MISSING_PERMISSIONS]))
            $message .= '. '.__('discord.events_permission_help');

        throw new DiscordException($message, $code);
    }

    public static function isPermissionError(DiscordException $e) {
        return in_array($e->getCode(), [self::ERROR_MISSING_ACCESS, self::ERROR_MISSING_PERMISSIONS]);
    }

    /**
     * The scheduled event for a website event, without its image.
     *
     * The meeting link is left out on purpose: the website only shares it shortly
     * before the event starts, and the server's event list is visible to everyone.
     */
    public static function scheduledEventPayload(Event $event) {
        list($start, $end) = self::scheduledEventTimes($event);
        $url = $event->absolute_permalink();

        // The summary if there is one, otherwise the description. Discord shows markdown but not HTML.
        $text = trim(strip_tags((string)($event->summary ?: $event->description)));
        $text = self::limitText($text, 1000 - mb_strlen($url) - 2);
        $description = $text ? $text."\n\n".$url : $url;

        $location = $event->has_physical_location() ? trim((string)$event->location_summary_with_name(), ', ') : '';
        if($location)
            $location = self::limitText($location, 100);
        else
            $location = mb_strlen($url) <= 100 ? $url : $event->absolute_shortlink();

        return [
            'name' => self::limitText($event->name, 100),
            'description' => $description,
            'scheduled_start_time' => $start,
            'scheduled_end_time' => $end,
            'privacy_level' => self::SCHEDULED_EVENT_PRIVACY_GUILD_ONLY,
            'entity_type' => self::SCHEDULED_EVENT_EXTERNAL,
            'channel_id' => null,
            'entity_metadata' => ['location' => $location],
        ];
    }

    /**
     * When the scheduled event starts and ends, as ISO 8601 times in UTC.
     *
     * Discord needs an end time. An event with a start time but no end time is taken to
     * last an hour. Events without times run from midnight at the start of their first
     * day to the end of their last day.
     */
    public static function scheduledEventTimes(Event $event) {
        $tz = new \DateTimeZone($event->timezone ?: 'UTC');
        $last_day = $event->is_multiday() ? $event->end_date : $event->start_date;

        if($event->start_time) {
            $start = new \DateTime($event->start_date.' '.$event->start_time, $tz);
            if($event->end_time) {
                $end = new \DateTime($last_day.' '.$event->end_time, $tz);
                // An end time earlier than the start time means the event runs past midnight
                if($end <= $start)
                    $end->modify('+1 day');
            } elseif($event->is_multiday()) {
                $end = new \DateTime($last_day.' 23:59:59', $tz);
            } else {
                $end = (clone $start)->modify('+1 hour');
            }
        } else {
            $start = new \DateTime($event->start_date.' 00:00:00', $tz);
            $end = new \DateTime($last_day.' 23:59:59', $tz);
        }

        $utc = new \DateTimeZone('UTC');
        return [
            $start->setTimezone($utc)->format('Y-m-d\TH:i:s\Z'),
            $end->setTimezone($utc)->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    // Cuts text to a maximum length, ending it with an ellipsis that counts towards the length
    private static function limitText($text, $max) {
        $text = trim((string)$text);
        if(mb_strlen($text) <= $max)
            return $text;
        return rtrim(mb_substr($text, 0, $max - 1)).'…';
    }

    // A fingerprint of everything that would be sent, with the cover image's URL standing in for its bytes
    public static function scheduledEventHash(Event $event, array $payload) {
        return hash('sha256', json_encode($payload + ['cover_image' => (string)$event->cover_image]));
    }

    // The event's cover image as a data URI for Discord, or null if there isn't a usable one
    public static function scheduledEventImage(Event $event) {
        if(!$event->cover_image)
            return null;

        if(preg_match('~^https?://~', $event->cover_image)) {
            $bytes = \App\Helpers\SafeHTTP::fetch_image($event->cover_image);
        } elseif(preg_match('~^/storage/(.+)$~', $event->cover_image, $match) && \Storage::exists('public/'.$match[1])) {
            $bytes = \Storage::get('public/'.$match[1]);
        } else {
            return null;
        }

        if(!$bytes || strlen($bytes) > self::SCHEDULED_EVENT_MAX_IMAGE_BYTES)
            return null;

        $info = @getimagesizefromstring($bytes);
        if(!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF]))
            return null;

        return 'data:'.$info['mime'].';base64,'.base64_encode($bytes);
    }

}
