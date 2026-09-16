<?php

namespace Tests\Feature;

use DateTime;
use DateTimeZone;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * An event's meeting link is only shared from 15 minutes before the event starts.
 */
class MeetingLinkTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    private function eventStartingIn($minutes, $meeting_url)
    {
        $start = new DateTime('now', new DateTimeZone('UTC'));
        $start->modify('+'.$minutes.' minutes');

        $event = $this->createEvent([
            'start_date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i'),
            'timezone' => 'UTC',
            'meeting_url' => $meeting_url,
        ]);

        // Look at the event as a visitor who isn't signed in
        $this->app['auth']->forgetGuards();

        return $event;
    }

    private function mcpMeetingUrl($event)
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => 'get_event', 'arguments' => ['key' => $event->key]],
        ])->assertOk();

        return json_decode($response->json('result.content.0.text'), true)['meeting_url'];
    }

    public function testTheLinkIsHiddenBeforeTheEvent()
    {
        $url = 'https://meet.example.com/'.uniqid('secret-');
        $event = $this->eventStartingIn(24 * 60, $url);

        $this->get($event->permalink())
            ->assertOk()
            ->assertDontSee($url, false)
            ->assertSee('The meeting link will be shown 15 minutes before the event');

        $this->assertNull($this->mcpMeetingUrl($event));
        $this->getJson('/event/'.$event->key.'.json')->assertJson(['meeting_url' => false]);
    }

    public function testTheLinkIsShownWhenTheEventIsAboutToStart()
    {
        $url = 'https://meet.example.com/'.uniqid('secret-');
        $event = $this->eventStartingIn(10, $url);

        $this->get($event->permalink())
            ->assertOk()
            ->assertSee($url, false);

        $this->assertEquals($url, $this->mcpMeetingUrl($event));
        $this->getJson('/event/'.$event->key.'.json')->assertJson(['meeting_url' => $url]);
    }
}
