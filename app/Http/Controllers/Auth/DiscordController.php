<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use DateTime, DateTimeZone, Exception;
use DB;
use App\User;
use Illuminate\Support\Str;

class DiscordController extends BaseController
{

    public static function discordAuthURL() {
        $state = bin2hex(random_bytes(16));

        session(['DISCORD_OAUTH_STATE' => $state]);

        $params = [
            'response_type' => 'code',
            'client_id' => env('DISCORD_CLIENT_ID'),
            'redirect_uri' => route('discord-oauth-redirect'),
            'state' => $state,
            'scope' => 'identify guilds.members.read',
        ];

        return 'https://discord.com/oauth2/authorize?' . http_build_query($params);
    }

    public function callback() {
        if(request('state') != session('DISCORD_OAUTH_STATE')) {
            return view('auth/oauth-error', [
                'error' => 'Invalid OAuth State',
                'error_description' => 'There was a problem with the login process. Double check you are allowing cookies from this domain and try again.',
            ]);
        }

        if(!request('code')) {
            return view('auth/oauth-error', [
                'error' => 'OAuth Error',
                'error_description' => 'The Discord login process did not complete successfully. Please try again.',
            ]);
        }

        $ch = curl_init('https://discord.com/api/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, \App\Helpers\HTTP::user_agent());
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'code' => request('code'),
            'redirect_uri' => route('discord-oauth-redirect'),
            'client_id' => env('DISCORD_CLIENT_ID'),
            'client_secret' => env('DISCORD_CLIENT_SECRET'),
        ]));
        $response = curl_exec($ch);
        $data = json_decode($response, true);

        if(!isset($data['access_token'])) {
            return view('auth/oauth-error', [
                'error' => 'OAuth Error',
                'error_description' => 'Unable to get an access token from Discord. Please try again.',
            ]);
        }

        $access_token = $data['access_token'];

        // Look up user info with this access token
        $ch = curl_init('https://discord.com/api/v10/oauth2/@me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Bearer '.$access_token,
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, \App\Helpers\HTTP::user_agent());
        $response = curl_exec($ch);
        $userdata = json_decode($response, true);

        if(!isset($userdata['user']['id'])) {
            return view('auth/oauth-error', [
                'error' => 'OAuth Error',
                'error_description' => 'Unable to get user info from Discord. Please try again.',
            ]);
        }

        if(env('DISCORD_SERVER_ID')) {
            // Check if user is a member of the server
            $ch = curl_init('https://discord.com/api/v10/users/@me/guilds/'.env('DISCORD_SERVER_ID').'/member');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Authorization: Bearer '.$access_token,
            ]);
            curl_setopt($ch, CURLOPT_USERAGENT, \App\Helpers\HTTP::user_agent());
            $response = curl_exec($ch);
            $guilddata = json_decode($response, true);

            if(!$guilddata || empty($guilddata['user'])) {
                return view('auth/oauth-error', [
                    'error' => 'User Not Allowed',
                    'error_description' => 'Sorry, you are not a member of the Discord server associated with this website.',
                ]);
            }

            if(env('DISCORD_ROLE_ID')) {
                // Check if this user has the required role
                if(!in_array(env('DISCORD_ROLE_ID'), $guilddata['roles'])) {
                    return view('auth/oauth-error', [
                        'error' => 'User Not Allowed',
                        'error_description' => 'Sorry, you are not assigned the required role in the Discord server.',
                    ]);
                }
            }

            $photo_url = 'https://cdn.discordapp.com/guilds/'.env('DISCORD_SERVER_ID').'/users/'.$userdata['user']['id'].'/avatars/'.$guilddata['avatar'].'.jpg';
        } else {
            $guilddata = null;
            $photo_url = 'https://cdn.discordapp.com/avatars/'.$userdata['user']['id'].'/'.$userdata['user']['avatar'].'.jpg';
        }

        // Create the user record if it doesn't yet exist
        $user = User::where('identifier', $userdata['user']['id'])->first();
        if(!$user) {
            $user = new User;
            $user->identifier = $userdata['user']['id'];
            $user->url = '';
            $user->photo = $user->downloadProfilePhoto($photo_url);
            $user->api_token = Str::random(80);
        }

        $user->name = $guilddata ? $guilddata['nick'] : $userdata['user']['username'];

        $user->save();

        // Now set the session data to make this user logged-in
        session([
            'DISCORD_USER' => $userdata['user']['id'],
        ]);

        if(session('AUTH_RETURN_TO')) {
            return redirect(session('AUTH_RETURN_TO'));
        } else {
            return redirect('/');
        }
    }

}
