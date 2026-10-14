<?php

namespace Tests\Feature;

use App\DiscordNotification;
use App\Event;
use App\Setting;
use App\Tag;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * Keeping the Discord server's event list in sync with upcoming website events.
 */
class DiscordScheduledEventTest extends TestCase
{
    use CreatesEvents;

    const ENV = ['AUTH_METHOD', 'DISCORD_BOT_TOKEN', 'DISCORD_SERVER_ID'];
    const SETTINGS = ['discord_sync_events', 'discord_sync_events_tags', 'discord_scheduled_events_error'];

    private $previous_env = [];
    private $previous_settings = [];
    private $tag;

    // Responses the fake Discord gives, by request method, changeable mid-test
    private $responses = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach(self::ENV as $name)
            $this->previous_env[$name] = $_SERVER[$name] ?? null;

        $this->setEnv('AUTH_METHOD', 'discord');
        $this->setEnv('DISCORD_BOT_TOKEN', 'test-bot-token');
        $this->setEnv('DISCORD_SERVER_ID', '1000');

        Setting::$cached = [];
        foreach(self::SETTINGS as $id)
            $this->previous_settings[$id] = Setting::value($id);

        $this->tag = 'discord-sync-'.uniqid();
        $this->configure(true, $this->tag);
        $this->forgetFakeIds();

        Http::preventStrayRequests();
    }

    // The fake Discord gives every event the same ID, which must not outlive a test
    private function forgetFakeIds()
    {
        Event::withTrashed()->where('discord_scheduled_event_id', '7777')
            ->update(['discord_scheduled_event_id' => '', 'discord_scheduled_event_hash' => '']);
    }

    protected function tearDown(): void
    {
        $this->forgetFakeIds();
        DiscordNotification::where('tag', $this->tag)->delete();

        $this->deleteTestData();

        Tag::where('tag', $this->tag)->delete();

        foreach($this->previous_settings as $id => $value)
            Setting::set($id, $value);
        Setting::$cached = [];

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

    private function configure($enabled, $tags = '')
    {
        Setting::set('discord_sync_events', $enabled ? 1 : 0);
        Setting::set('discord_sync_events_tags', $tags);
        Setting::set('discord_scheduled_events_error', '');
        Setting::$cached = [];
    }

    private function fakeDiscord()
    {
        $this->responses = [
            'POST' => [200, ['id' => '7777']],
            'PATCH' => [200, ['id' => '7777']],
            'DELETE' => [204, ''],
        ];

        Http::fake([
            'discord.com/api/v10/guilds/1000/scheduled-events*' => function($request) {
                list($status, $body) = $this->responses[$request->method()];
                return Http::response($body, $status);
            },
            'discord.com/api/v10/guilds/1000/channels' => Http::response([
                ['id' => '20', 'type' => 0, 'name' => 'events', 'position' => 0],
            ]),
            'discord.com/api/v10/guilds/1000/roles' => Http::response([
                ['id' => '1000', 'name' => '@everyone', 'permissions' => '19456'],
            ]),
            'discord.com/api/v10/guilds/1000/members/9000' => Http::response(['user' => ['id' => '9000'], 'roles' => []]),
            'discord.com/api/v10/users/@me' => Http::response(['id' => '9000', 'username' => 'Events Bot']),
            'discord.com/api/v10/guilds/1000' => Http::response(['id' => '1000', 'name' => 'Test Server']),
        ]);
    }

    private function eventStartingIn($minutes, array $fields = [])
    {
        $start = new DateTime('now', new DateTimeZone('UTC'));
        $start->modify(($minutes < 0 ? '' : '+').$minutes.' minutes');

        return $this->createEvent(array_merge([
            'start_date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i'),
            'timezone' => 'UTC',
            'tags' => $this->tag,
            'meeting_url' => 'https://meet.example.com/secret',
            'summary' => 'A test event',
        ], $fields));
    }

    private function upcomingEvent(array $fields = [])
    {
        return $this->eventStartingIn(120, $fields);
    }

    private function sync()
    {
        $this->artisan('discord:sync-events')->assertExitCode(0);
    }

    // The scheduled event requests made so far, optionally only those of one method
    private function requests($method = null)
    {
        return Http::recorded(function($request) use($method){
            return str_contains($request->url(), '/scheduled-events')
                && (!$method || $request->method() == $method);
        })->values();
    }

    public function testAnUpcomingEventIsAddedOnce()
    {
        $this->fakeDiscord();
        $event = $this->upcomingEvent();

        $this->sync();
        $this->sync();

        $posts = $this->requests('POST');
        $this->assertCount(1, $posts);
        $this->assertCount(1, $this->requests());

        list($request) = $posts[0];
        $this->assertStringEndsWith('/guilds/1000/scheduled-events', $request->url());
        $this->assertEquals('Bot test-bot-token', $request->header('Authorization')[0]);
        $this->assertNotEmpty($request->header('X-Audit-Log-Reason')[0]);
        $this->assertEquals($event->name, $request['name']);
        $this->assertEquals(gmdate('Y-m-d\TH:i:s\Z', strtotime($event->sort_date)), $request['scheduled_start_time']);
        $this->assertEquals(gmdate('Y-m-d\TH:i:s\Z', strtotime($event->sort_date) + 3600), $request['scheduled_end_time']);
        $this->assertEquals(3, $request['entity_type']);
        $this->assertEquals(2, $request['privacy_level']);
        $this->assertNull($request['channel_id']);
        $this->assertEquals($event->absolute_permalink(), $request['entity_metadata']['location']);
        $this->assertStringContainsString($event->absolute_permalink(), $request['description']);
        $this->assertStringNotContainsString('meet.example.com', json_encode($request->data()));

        $event = $event->fresh();
        $this->assertEquals('7777', $event->discord_scheduled_event_id);
        $this->assertEquals(64, strlen($event->discord_scheduled_event_hash));
    }

    public function testChangesAreSentAsUpdates()
    {
        $this->fakeDiscord();
        $event = $this->upcomingEvent();
        $this->sync();
        $hash = $event->fresh()->discord_scheduled_event_hash;

        // Test events are cleaned up by name, so the new name has to be remembered too
        $new_name = 'Renamed Event '.uniqid();
        $this->forgetEvent($new_name);
        $event->name = $new_name;
        $event->save();
        $this->sync();

        $patches = $this->requests('PATCH');
        $this->assertCount(1, $patches);
        $this->assertStringEndsWith('/scheduled-events/7777', $patches[0][0]->url());
        $this->assertEquals($new_name, $patches[0][0]['name']);
        $this->assertNotEquals($hash, $event->fresh()->discord_scheduled_event_hash);

        $start = (new DateTime('now', new DateTimeZone('UTC')))->modify('+3 hours');
        $event->start_date = $start->format('Y-m-d');
        $event->start_time = $start->format('H:i:00');
        $event->sort_date = $event->sort_date();
        $event->save();
        $this->sync();

        $patches = $this->requests('PATCH');
        $this->assertCount(2, $patches);
        $this->assertEquals($start->format('Y-m-d\TH:i:00\Z'), $patches[1][0]['scheduled_start_time']);
        $this->assertCount(1, $this->requests('POST'));
    }

    public function testACancelledEventIsMarkedCancelledInDiscord()
    {
        $this->fakeDiscord();
        $event = $this->upcomingEvent();
        $this->sync();

        $event->status = 'cancelled';
        $event->save();
        $this->sync();

        $patches = $this->requests('PATCH');
        $this->assertCount(1, $patches);
        $this->assertStringEndsWith('/scheduled-events/7777', $patches[0][0]->url());
        $this->assertEquals(4, $patches[0][0]['status']);
        $this->assertCount(0, $this->requests('DELETE'));
        $this->assertEquals('', $event->fresh()->discord_scheduled_event_id);

        // Discord won't change a cancelled event, so an un-cancelled event gets a new one
        $event->status = 'confirmed';
        $event->save();
        $this->sync();

        $this->assertCount(2, $this->requests('POST'));
        $this->assertEquals('7777', $event->fresh()->discord_scheduled_event_id);
    }

    public function testEventsThatNoLongerBelongAreRemovedFromDiscord()
    {
        $this->fakeDiscord();

        $removals = [
            'deleted' => function($event) { $event->delete(); },
            'unlisted' => function($event) { $event->unlisted = 1; $event->save(); },
            'tag removed' => function($event) { DB::table('event_tag')->where('event_id', $event->id)->delete(); },
            'filter changed' => function($event) { $this->configure(true, 'some-other-tag'); },
            'turned off' => function($event) { $this->configure(false, $this->tag); },
        ];

        foreach($removals as $description => $remove) {
            $this->configure(true, $this->tag);
            $event = $this->upcomingEvent();
            $this->sync();
            $deletes_before = count($this->requests('DELETE'));

            $remove($event);
            $this->sync();

            $this->assertCount($deletes_before + 1, $this->requests('DELETE'), $description."\n".$this->requestLog());
            $this->assertStringEndsWith('/scheduled-events/7777', $this->requests('DELETE')[$deletes_before][0]->url(), $description);
            $this->assertEquals('', Event::withTrashed()->find($event->id)->discord_scheduled_event_id, $description);

            // Otherwise an event only kept out by the filter or the switch would come back next time round
            $event->delete();
        }

        $this->assertCount(count($removals), $this->requests('POST'), $this->requestLog());
    }

    // The requests made so far, for assertion messages
    private function requestLog()
    {
        return $this->requests()->map(function($pair){
            return $pair[0]->method().' '.$pair[0]->url().' '.json_encode(['name' => $pair[0]['name'] ?? null, 'status' => $pair[0]['status'] ?? null]);
        })->implode("\n");
    }

    public function testEventsThatShouldNotBeAddedAreSkipped()
    {
        $this->fakeDiscord();

        $this->eventStartingIn(-30);
        $this->eventStartingIn(1);
        $this->upcomingEvent(['unlisted' => 1]);
        $this->upcomingEvent(['status' => 'cancelled']);
        $this->upcomingEvent(['tags' => 'some-other-tag-'.uniqid()]);

        $this->sync();

        $this->assertCount(0, $this->requests());

        Tag::where('tag', 'like', 'some-other-tag-%')->delete();
    }

    public function testEveryPublicEventIsAddedWhenThereIsNoTagFilter()
    {
        $this->fakeDiscord();
        $this->configure(true, '');

        $other = 'some-other-tag-'.uniqid();
        $event = $this->upcomingEvent(['tags' => $other]);

        $this->sync();

        $names = $this->requests('POST')->map(fn($pair) => $pair[0]['name'])->all();
        $this->assertContains($event->name, $names);

        Tag::where('tag', $other)->delete();
    }

    public function testAChannelNotificationAloneAddsNothing()
    {
        $this->fakeDiscord();
        $this->configure(false, '');

        $notification = new DiscordNotification;
        $notification->tag = $this->tag;
        $notification->channel_id = '20';
        $notification->channel_name = 'events';
        $notification->minutes_before = 60;
        $notification->enabled = true;
        $notification->save();

        $this->upcomingEvent();
        $this->sync();

        $this->assertCount(0, $this->requests());
    }

    public function testAnEventAlreadyGoneFromDiscordIsForgottenQuietly()
    {
        $this->fakeDiscord();
        $event = $this->upcomingEvent();
        $this->sync();

        $this->responses['DELETE'] = [404, ['message' => 'Unknown Guild Scheduled Event', 'code' => 10070]];
        $event->unlisted = 1;
        $event->save();
        $this->sync();

        $this->assertCount(1, $this->requests('DELETE'));
        $this->assertEquals('', $event->fresh()->discord_scheduled_event_id);
        $this->assertEquals('', Setting::value('discord_scheduled_events_error'));
    }

    public function testAFailedRequestIsTriedAgainAndShown()
    {
        $this->fakeDiscord();
        $this->responses['POST'] = [403, ['message' => 'Missing Permissions', 'code' => 50013]];
        $event = $this->upcomingEvent();

        $this->sync();

        $this->assertEquals('', $event->fresh()->discord_scheduled_event_id);
        Setting::$cached = [];
        $this->assertStringContainsString('Missing Permissions', Setting::value('discord_scheduled_events_error'));
        $this->assertStringContainsString('Manage Events', Setting::value('discord_scheduled_events_error'));

        $this->actingAs($this->testUser())->get('/discord')
            ->assertOk()
            ->assertSee('The last attempt to update the server');

        $this->responses['POST'] = [200, ['id' => '7777']];
        $this->sync();

        $this->assertEquals('7777', $event->fresh()->discord_scheduled_event_id);
        Setting::$cached = [];
        $this->assertEquals('', Setting::value('discord_scheduled_events_error'));
    }

    public function testAStartedEventIsLeftForDiscordToFinish()
    {
        $this->fakeDiscord();
        $event = $this->eventStartingIn(-30);
        Event::where('id', $event->id)->update(['discord_scheduled_event_id' => '7777', 'discord_scheduled_event_hash' => 'x']);

        $this->sync();

        $this->assertCount(0, $this->requests());
        $this->assertEquals('', $event->fresh()->discord_scheduled_event_id);
    }

    public function testTheCoverImageIsSent()
    {
        $this->fakeDiscord();

        // The app's storage folder belongs to the web server, so use a disk of our own
        $root = sys_get_temp_dir().'/meetable-test-'.uniqid();
        Storage::set(config('filesystems.default'), Storage::createLocalDriver(['root' => $root]));

        $image = new \Imagick();
        $image->newImage(2, 2, 'red');
        $image->setImageFormat('jpeg');
        Storage::put('public/events/test-cover.jpg', $image->getImageBlob());

        $event = $this->upcomingEvent();
        $event->cover_image = '/storage/events/test-cover.jpg';
        $event->save();

        $this->sync();

        $posts = $this->requests('POST');
        $this->assertCount(1, $posts);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $posts[0][0]['image']);

        Storage::deleteDirectory('public');
        @rmdir($root);
    }

    public function testTheSettingsFormSavesTheSwitchAndTags()
    {
        $this->fakeDiscord();

        $this->actingAs($this->testUser())
            ->post('/discord/events/save', ['enabled' => 1, 'tags' => 'Foo, BAR baz'])
            ->assertRedirect('/discord');

        Setting::$cached = [];
        $this->assertEquals(1, Setting::value('discord_sync_events'));
        $this->assertEquals('foo bar baz', Setting::value('discord_sync_events_tags'));

        $this->actingAs($this->testUser())->get('/discord')
            ->assertOk()
            ->assertSee('name="enabled" value="1" checked', false)
            ->assertSee('value="foo bar baz"', false);

        $this->actingAs($this->testUser())
            ->post('/discord/events/save', ['tags' => ''])
            ->assertRedirect('/discord');

        Setting::$cached = [];
        $this->assertEquals(0, Setting::value('discord_sync_events'));
        $this->assertEquals('', Setting::value('discord_sync_events_tags'));
    }
}
