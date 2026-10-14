<?php

namespace Tests\Feature;

use App\Setting;
use DateTime;
use DateTimeZone;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * An event's meeting link is only shared from 15 minutes before the event starts, and
 * only reads "join" once the meeting is live. Zoom says when its meetings start; other
 * platforms don't, so for them the start time is what counts.
 */
class MeetingLinkTest extends TestCase
{
    use CreatesEvents;

    private $previous_webhook_secret;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::$cached = [];
        $this->previous_webhook_secret = Setting::value('zoom_webhook_secret');
    }

    protected function tearDown(): void
    {
        $this->deleteTestData();

        Setting::set('zoom_webhook_secret', $this->previous_webhook_secret);
        Setting::$cached = [];

        parent::tearDown();
    }

    private function zoomWebhookConfigured(bool $configured)
    {
        Setting::set('zoom_webhook_secret', $configured ? 'test-secret' : null);
        Setting::$cached = [];
    }

    private function eventStartingIn($minutes, $meeting_url)
    {
        $start = new DateTime('now', new DateTimeZone('UTC'));
        $start->modify(($minutes < 0 ? '' : '+').$minutes.' minutes');

        $event = $this->createEvent([
            'start_date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i'),
            'end_time' => $start->modify('+2 hours')->format('H:i'),
            'timezone' => 'UTC',
            'meeting_url' => $meeting_url,
        ]);

        // Look at the event as a visitor who isn't signed in
        $this->app['auth']->forgetGuards();

        return $event;
    }

    private function zoomEventStartingIn($minutes, $meeting_url)
    {
        $event = $this->eventStartingIn($minutes, $meeting_url);
        $event->zoom_meeting_id = (string)random_int(10000000000, 99999999999);
        $event->save();

        return $event;
    }

    private function mcpEvent($event)
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => 'get_event', 'arguments' => ['key' => $event->key]],
        ])->assertOk();

        return json_decode($response->json('result.content.0.text'), true);
    }

    public function testTheLinkIsHiddenBeforeTheEvent()
    {
        $url = 'https://meet.example.com/'.uniqid('secret-');
        $event = $this->eventStartingIn(24 * 60, $url);

        $this->get($event->permalink())
            ->assertOk()
            ->assertDontSee($url, false)
            ->assertSee('data-state="hidden"', false)
            ->assertSee('The meeting link will be shown 15 minutes before the event');

        $this->assertNull($this->mcpEvent($event)['meeting_url']);
        $this->getJson('/event/'.$event->key.'.json')
            ->assertJson(['meeting_url' => false, 'meeting_state' => 'hidden']);
    }

    public function testTheLinkIsSharedBeforeTheMeetingStarts()
    {
        $url = 'https://meet.example.com/'.uniqid('secret-');
        $event = $this->eventStartingIn(10, $url);

        $this->get($event->permalink())
            ->assertOk()
            ->assertSee($url, false)
            ->assertSee('data-state="soon"', false)
            ->assertDontSee('Live Now');

        $this->assertEquals($url, $this->mcpEvent($event)['meeting_url']);
        $this->assertEquals('soon', $this->mcpEvent($event)['meeting_state']);
        $this->getJson('/event/'.$event->key.'.json')
            ->assertJson(['meeting_url' => $url, 'meeting_state' => 'soon']);
    }

    /**
     * Platforms like Google Meet don't say when a meeting starts, so once the start
     * time has passed the meeting is taken to be live.
     */
    public function testAMeetingWithNoStartSignalIsLiveFromItsStartTime()
    {
        $url = 'https://meet.example.com/'.uniqid('secret-');
        $event = $this->eventStartingIn(-5, $url);

        // The status tag renders its first letter apart from the rest
        $this->get($event->permalink())
            ->assertOk()
            ->assertSee($url, false)
            ->assertSee('data-state="live"', false)
            ->assertSee('IVE NOW', false);

        $this->getJson('/event/'.$event->key.'.json')->assertJson(['meeting_state' => 'live']);
    }

    public function testAZoomMeetingIsLiveOnlyOnceZoomSaysSo()
    {
        $this->zoomWebhookConfigured(true);

        $url = 'https://zoom.example.com/'.uniqid('secret-');
        $event = $this->zoomEventStartingIn(-5, $url);

        // Past the start time, but Zoom hasn't reported the meeting starting
        $this->get($event->permalink())
            ->assertOk()
            ->assertSee($url, false)
            ->assertSee('data-state="soon"', false)
            ->assertDontSee('IVE NOW', false);
        $this->getJson('/event/'.$event->key.'.json')->assertJson(['meeting_state' => 'soon']);

        $event->zoom_meeting_status = 'started';
        $event->save();

        $this->get($event->permalink())
            ->assertSee('data-state="live"', false)
            ->assertSee('IVE NOW', false);
        $this->getJson('/event/'.$event->key.'.json')->assertJson(['meeting_state' => 'live']);

        $event->zoom_meeting_status = 'ended';
        $event->save();

        $this->get($event->permalink())
            ->assertDontSee($url, false)
            ->assertDontSee('id="meeting-segment"', false);
        $this->getJson('/event/'.$event->key.'.json')->assertJson(['meeting_state' => 'over']);
    }

    /**
     * Without the webhook set up, Zoom never reports anything, so a Zoom meeting is
     * treated like any other platform.
     */
    public function testWithoutTheZoomWebhookTheStartTimeIsUsed()
    {
        $this->zoomWebhookConfigured(false);

        $url = 'https://zoom.example.com/'.uniqid('secret-');
        $event = $this->zoomEventStartingIn(-5, $url);

        $this->get($event->permalink())
            ->assertOk()
            ->assertSee('data-state="live"', false);
    }
}
