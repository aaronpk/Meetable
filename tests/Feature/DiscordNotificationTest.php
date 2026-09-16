<?php

namespace Tests\Feature;

use App\DiscordNotification;
use App\DiscordNotificationSend;
use App\Event;
use App\Services\Discord;
use App\Tag;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Tests\CreatesEvents;
use Tests\TestCase;

class DiscordNotificationTest extends TestCase
{
    use CreatesEvents;

    const ENV = ['AUTH_METHOD', 'DISCORD_BOT_TOKEN', 'DISCORD_SERVER_ID'];

    private $previous_env = [];
    private $tag;

    protected function setUp(): void
    {
        parent::setUp();

        // The login guard was already chosen from phpunit.xml when the app booted, so tests still log in
        // through the session guard, while the Discord features see a site that uses Discord login.
        foreach(self::ENV as $name)
            $this->previous_env[$name] = $_SERVER[$name] ?? null;

        $this->setEnv('AUTH_METHOD', 'discord');
        $this->setEnv('DISCORD_BOT_TOKEN', 'test-bot-token');
        $this->setEnv('DISCORD_SERVER_ID', '1000');

        $this->tag = 'discord-test-'.uniqid();

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        $ids = DiscordNotification::where('tag', $this->tag)->pluck('id');
        DiscordNotificationSend::whereIn('discord_notification_id', $ids)->delete();
        DiscordNotification::whereIn('id', $ids)->delete();

        $this->deleteTestData();

        Tag::where('tag', $this->tag)->delete();

        foreach($this->previous_env as $name => $value)
            $this->setEnv($name, $value);

        parent::tearDown();
    }

    private function setEnv($name, $value)
    {
        if($value === null)
            unset($_SERVER[$name]);
        else
            $_SERVER[$name] = $value;
    }

    private function fakeDiscord($status = 200)
    {
        Http::fake([
            'discord.com/api/v10/channels/*/messages' => Http::response(
                $status == 200 ? ['id' => '5555'] : ['message' => 'Missing Access', 'code' => 50001],
                $status
            ),
            'discord.com/api/v10/guilds/1000/channels' => Http::response([
                ['id' => '10', 'type' => 4, 'name' => 'Community', 'position' => 1],
                ['id' => '20', 'type' => 0, 'name' => 'events', 'position' => 2, 'parent_id' => '10'],
                ['id' => '30', 'type' => 2, 'name' => 'Voice', 'position' => 3, 'parent_id' => '10'],
                ['id' => '40', 'type' => 0, 'name' => 'general', 'position' => 0],
                ['id' => '50', 'type' => 0, 'name' => 'private', 'position' => 1, 'permission_overwrites' => [
                    ['id' => '1000', 'type' => 0, 'allow' => '0', 'deny' => '1024'],
                ]],
            ]),
            'discord.com/api/v10/guilds/1000/roles' => Http::response([
                ['id' => '1000', 'name' => '@everyone', 'permissions' => '1024'],
                ['id' => '2000', 'name' => 'Events Bot', 'permissions' => '19456'],
            ]),
            'discord.com/api/v10/guilds/1000/members/9000' => Http::response(['user' => ['id' => '9000'], 'roles' => ['2000']]),
            'discord.com/api/v10/users/@me' => Http::response(['id' => '9000', 'username' => 'Events Bot']),
            'discord.com/api/v10/guilds/1000' => Http::response(['id' => '1000', 'name' => 'Test Server']),
        ]);
    }

    private function mapping(array $fields = []): DiscordNotification
    {
        $notification = new DiscordNotification;
        $notification->tag = $this->tag;
        $notification->channel_id = '20';
        $notification->channel_name = 'events';
        $notification->minutes_before = 60;
        $notification->message = 'Starting soon, <@&777> come hang out!';
        $notification->enabled = true;
        foreach($fields as $k => $v)
            $notification->{$k} = $v;
        $notification->save();
        return $notification;
    }

    private function eventStartingIn($minutes, array $fields = [])
    {
        $start = new DateTime('now', new DateTimeZone('UTC'));
        $start->modify('+'.$minutes.' minutes');

        return $this->createEvent(array_merge([
            'start_date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i'),
            'timezone' => 'UTC',
            'tags' => $this->tag,
            'meeting_url' => 'https://meet.example.com/abc',
            'summary' => 'A test event',
        ], $fields));
    }

    private function messagesSent()
    {
        return Http::recorded(function($request){
            return str_ends_with($request->url(), '/messages');
        });
    }

    public function testAnEventStartingWithinTheWindowIsPostedOnce()
    {
        $this->fakeDiscord();
        $notification = $this->mapping();
        $event = $this->eventStartingIn(30);

        $this->artisan('discord:notify')->assertExitCode(0);
        $this->artisan('discord:notify')->assertExitCode(0);

        $sent = $this->messagesSent();
        $this->assertCount(1, $sent);

        list($request) = $sent[0];
        $this->assertStringEndsWith('/channels/20/messages', $request->url());
        $this->assertEquals('Bot test-bot-token', $request->header('Authorization')[0]);
        $this->assertStringStartsWith('DiscordBot (', $request->header('User-Agent')[0]);
        $this->assertEquals('Starting soon, <@&777> come hang out!', $request['content']);
        $this->assertEquals($event->name, $request['embeds'][0]['title']);
        $this->assertEquals($event->absolute_permalink(), $request['embeds'][0]['url']);
        $this->assertEquals('A test event', $request['embeds'][0]['description']);
        $this->assertStringContainsString('<t:'.$event->start_datetime()->format('U').':F>', $request['embeds'][0]['fields'][0]['value']);
        $this->assertEquals('https://meet.example.com/abc', $request['embeds'][0]['fields'][1]['value']);
        $this->assertEquals(['users', 'roles'], $request['allowed_mentions']['parse']);

        $send = DiscordNotificationSend::where('discord_notification_id', $notification->id)->first();
        $this->assertEquals($event->id, $send->event_id);
        $this->assertEquals('5555', $send->discord_message_id);
    }

    public function testEventsThatShouldNotBeAnnouncedAreSkipped()
    {
        $this->fakeDiscord();
        $this->mapping();

        $this->eventStartingIn(120);
        $this->eventStartingIn(-30);
        $this->eventStartingIn(30, ['tags' => 'some-other-tag-'.uniqid()]);
        $this->eventStartingIn(30, ['status' => 'cancelled']);
        $this->eventStartingIn(30, ['unlisted' => 1]);
        $this->mapping(['enabled' => false, 'minutes_before' => 180]);

        $this->artisan('discord:notify')->assertExitCode(0);

        $this->assertCount(0, $this->messagesSent());

        Tag::where('tag', 'like', 'some-other-tag-%')->delete();
    }

    public function testATemplateIsNotAnnouncedButItsEventsAre()
    {
        $notification = $this->mapping();
        $event = $this->eventStartingIn(30, ['is_template' => 1, 'recurrence_interval' => 'weekly_dow']);

        $template = Event::where('name', $event->name)->where('is_template', 1)->firstOrFail();
        $instance = Event::where('name', $event->name)->where('is_template', 0)->orderBy('sort_date')->firstOrFail();

        $due = $notification->eventsDue(new DateTime('now', new DateTimeZone('UTC')))->pluck('id')->all();

        $this->assertNotContains($template->id, $due);
        $this->assertContains($instance->id, $due);
    }

    public function testARescheduledEventIsAnnouncedAgain()
    {
        $this->fakeDiscord();
        $this->mapping();
        $event = $this->eventStartingIn(30);

        $this->artisan('discord:notify');

        $start = new DateTime('now', new DateTimeZone('UTC'));
        $start->modify('+45 minutes');
        $event->start_date = $start->format('Y-m-d');
        $event->start_time = $start->format('H:i:00');
        $event->sort_date = $event->sort_date();
        $event->save();

        $this->artisan('discord:notify');

        $this->assertCount(2, $this->messagesSent());
    }

    public function testMissingAccessExplainsHowToFixTheChannel()
    {
        $this->fakeDiscord(403);
        $notification = $this->mapping();

        $this->actingAs($this->testUser())
            ->post('/discord/'.$notification->id.'/test')
            ->assertRedirect('/discord')
            ->assertSessionHas('discord-error', function($message){
                return str_contains($message, 'Missing Access')
                    && str_contains($message, 'allow the "Events Bot" role or member to View Channel, Send Messages and Embed Links');
            });

        $this->actingAs($this->testUser())
            ->get('/discord')
            ->assertOk()
            ->assertDontSee('Bot needs access');

        $notification->channel_id = '50';
        $notification->save();

        $this->actingAs($this->testUser())
            ->get('/discord')
            ->assertOk()
            ->assertSee('Bot needs access');
    }

    public function testChannelPermissionsFollowDiscordsOverwriteRules()
    {
        $roles = [
            ['id' => '1', 'permissions' => (string)(1024 | 2048 | 16384)],
            ['id' => '2', 'permissions' => '0'],
            ['id' => '3', 'permissions' => '8'],
        ];
        $member = ['user' => ['id' => '99'], 'roles' => ['2']];
        $can = function($overwrites, $member_roles = ['2']) use($roles, $member) {
            $member['roles'] = $member_roles;
            return Discord::canPost(Discord::channelPermissions('1', $roles, $member, ['permission_overwrites' => $overwrites]));
        };

        $this->assertTrue($can([]));
        // A private channel
        $this->assertFalse($can([['id' => '1', 'type' => 0, 'allow' => '0', 'deny' => '1024']]));
        // ...where the bot's role was added
        $this->assertTrue($can([['id' => '1', 'type' => 0, 'allow' => '0', 'deny' => '1024'], ['id' => '2', 'type' => 0, 'allow' => '1024', 'deny' => '0']]));
        // ...where the bot itself was added
        $this->assertTrue($can([['id' => '1', 'type' => 0, 'allow' => '0', 'deny' => '1024'], ['id' => '99', 'type' => 1, 'allow' => '1024', 'deny' => '0']]));
        // A member overwrite beats a role overwrite
        $this->assertFalse($can([['id' => '2', 'type' => 0, 'allow' => '2048', 'deny' => '0'], ['id' => '99', 'type' => 1, 'allow' => '0', 'deny' => '2048']]));
        // A read-only channel
        $this->assertFalse($can([['id' => '1', 'type' => 0, 'allow' => '0', 'deny' => '2048']]));
        // Administrators can post anywhere
        $this->assertTrue($can([['id' => '1', 'type' => 0, 'allow' => '0', 'deny' => '1024']], ['2', '3']));
    }

    public function testAFailedPostIsNotRecorded()
    {
        $this->fakeDiscord(403);
        $notification = $this->mapping();
        $this->eventStartingIn(30);

        $this->artisan('discord:notify')->assertExitCode(0);

        $this->assertCount(1, $this->messagesSent());
        $this->assertEquals(0, $notification->sends()->count());
    }

    public function testNothingIsPostedWithoutABotToken()
    {
        $this->fakeDiscord();
        $this->setEnv('DISCORD_BOT_TOKEN', null);
        $this->mapping();
        $this->eventStartingIn(30);

        $this->artisan('discord:notify');

        $this->assertCount(0, $this->messagesSent());
    }

    public function testAUserCanCreateANotification()
    {
        $this->fakeDiscord();

        $this->actingAs($this->testUser())
            ->get('/discord/new')
            ->assertOk()
            ->assertSee('#events')
            ->assertSee('#general')
            ->assertSee('#private (bot needs access)')
            ->assertDontSee('#events (bot needs access)')
            ->assertDontSee('#Voice');

        $this->actingAs($this->testUser())
            ->post('/discord/save', [
                'tag' => strtoupper($this->tag),
                'channel_id' => '20',
                'time_before' => '2',
                'time_unit' => 'hours',
                'message' => 'Hello',
                'enabled' => '1',
            ])
            ->assertRedirect('/discord');

        $notification = DiscordNotification::where('tag', $this->tag)->firstOrFail();
        $this->assertEquals('events', $notification->channel_name);
        $this->assertEquals(120, $notification->minutes_before);
        $this->assertEquals('2 hours', $notification->timeBeforeText());
        $this->assertEquals($this->testUser()->id, $notification->created_by);

        $this->actingAs($this->testUser())
            ->get('/discord')
            ->assertOk()
            ->assertSee('Test Server')
            ->assertSee('#'.$this->tag);
    }

    public function testAChannelNotInTheServerIsRejected()
    {
        $this->fakeDiscord();

        $this->actingAs($this->testUser())
            ->post('/discord/save', [
                'tag' => $this->tag,
                'channel_id' => '30',
                'time_before' => '15',
                'time_unit' => 'minutes',
            ])
            ->assertRedirect('/discord/new');

        $this->assertEquals(0, DiscordNotification::where('tag', $this->tag)->count());
    }

    public function testTheTestButtonPostsTheNextEvent()
    {
        $this->fakeDiscord();
        $notification = $this->mapping();
        $event = $this->eventStartingIn(24 * 60);

        $this->actingAs($this->testUser())
            ->post('/discord/'.$notification->id.'/test')
            ->assertRedirect('/discord')
            ->assertSessionHas('discord-success');

        $sent = $this->messagesSent();
        $this->assertCount(1, $sent);
        $this->assertEquals($event->name, $sent[0][0]['embeds'][0]['title']);

        // A test doesn't count as the real reminder
        $this->assertEquals(0, $notification->sends()->count());
    }

    public function testTheInstallCallbackChecksTheState()
    {
        $this->fakeDiscord();

        $this->actingAs($this->testUser())
            ->withSession(['DISCORD_INSTALL_STATE' => 'expected'])
            ->get('/discord/install?state=wrong&guild_id=1000')
            ->assertRedirect('/discord')
            ->assertSessionHas('discord-error');

        $this->actingAs($this->testUser())
            ->withSession(['DISCORD_INSTALL_STATE' => 'expected'])
            ->get('/discord/install?state=expected&guild_id=1000&code=abc&permissions=19456')
            ->assertRedirect('/discord')
            ->assertSessionHas('discord-success', 'The bot was installed in Test Server');
    }

    public function testTheStatusShowsWhenTheBotIsNotInTheServer()
    {
        Http::fake([
            'discord.com/api/v10/guilds/1000' => Http::response(['message' => 'Missing Access', 'code' => 50001], 403),
        ]);

        $this->actingAs($this->testUser())
            ->get('/discord')
            ->assertOk()
            ->assertSee('The bot has not been added to the Discord server yet.')
            ->assertSee('/discord/install/start');
    }

    public function testOtherDiscordErrorsAreShownInsteadOfTheInstallButton()
    {
        Http::fake([
            'discord.com/api/v10/guilds/1000' => Http::response(['message' => 'internal network error', 'code' => 40333], 403),
        ]);

        $this->actingAs($this->testUser())
            ->get('/discord')
            ->assertOk()
            ->assertSee('Discord API error (403): internal network error');
    }

    public function testThePagesAreOnlyAvailableOnSitesUsingDiscordLogin()
    {
        $this->get('/discord')->assertRedirect();

        $this->setEnv('AUTH_METHOD', 'session');

        $this->actingAs($this->testUser())
            ->get('/discord')
            ->assertNotFound();
    }
}
