<?php

namespace Tests\Unit;

use App\Event;
use App\Services\Discord;
use Tests\TestCase;

/**
 * What is sent to Discord for an event's entry in the server's event list.
 */
class DiscordScheduledEventPayloadTest extends TestCase
{
    private function event(array $fields = []): Event
    {
        $event = new Event;
        $event->key = 'abcdefghijkl';
        $event->slug = 'test-event';
        $event->name = 'Test Event';
        $event->start_date = '2032-01-01';
        $event->timezone = 'America/Los_Angeles';
        foreach($fields as $k => $v)
            $event->{$k} = $v;
        return $event;
    }

    public function testAnAllDayEventRunsFromMidnightToTheEndOfTheDay()
    {
        $this->assertEquals(
            ['2032-01-01T08:00:00Z', '2032-01-02T07:59:59Z'],
            Discord::scheduledEventTimes($this->event())
        );
    }

    public function testAMultiDayEventEndsAtTheEndOfItsLastDay()
    {
        $this->assertEquals(
            ['2032-01-01T08:00:00Z', '2032-01-04T07:59:59Z'],
            Discord::scheduledEventTimes($this->event(['end_date' => '2032-01-03']))
        );
    }

    public function testAnEventWithOnlyAStartTimeLastsAnHour()
    {
        $this->assertEquals(
            ['2032-01-01T18:00:00Z', '2032-01-01T19:00:00Z'],
            Discord::scheduledEventTimes($this->event(['start_time' => '10:00:00']))
        );
    }

    public function testAnEventWithAnEndTimeUsesIt()
    {
        $this->assertEquals(
            ['2032-01-01T18:00:00Z', '2032-01-01T20:30:00Z'],
            Discord::scheduledEventTimes($this->event(['start_time' => '10:00:00', 'end_time' => '12:30:00']))
        );
    }

    public function testAnEndTimeBeforeTheStartTimeIsTheNextDay()
    {
        $this->assertEquals(
            ['2032-01-02T06:00:00Z', '2032-01-02T09:00:00Z'],
            Discord::scheduledEventTimes($this->event(['start_time' => '22:00:00', 'end_time' => '01:00:00']))
        );
    }

    public function testATimedMultiDayEventEndsOnItsLastDay()
    {
        $this->assertEquals(
            ['2032-01-01T18:00:00Z', '2032-01-03T01:00:00Z'],
            Discord::scheduledEventTimes($this->event(['start_time' => '10:00:00', 'end_time' => '17:00:00', 'end_date' => '2032-01-02']))
        );
    }

    public function testAnEventWithoutATimezoneIsInUtc()
    {
        $this->assertEquals(
            ['2032-01-01T10:00:00Z', '2032-01-01T11:00:00Z'],
            Discord::scheduledEventTimes($this->event(['timezone' => null, 'start_time' => '10:00:00']))
        );
    }

    public function testThePayloadDescribesAnExternalEventLinkingToTheSite()
    {
        $event = $this->event(['summary' => 'Come <b>hang out</b>', 'meeting_url' => 'https://meet.example.com/secret']);
        $payload = Discord::scheduledEventPayload($event);

        $this->assertEquals('Test Event', $payload['name']);
        $this->assertEquals("Come hang out\n\n".$event->absolute_permalink(), $payload['description']);
        $this->assertEquals(3, $payload['entity_type']);
        $this->assertEquals(2, $payload['privacy_level']);
        $this->assertNull($payload['channel_id']);
        // An online event's location is the event page, never the meeting link
        $this->assertEquals($event->absolute_permalink(), $payload['entity_metadata']['location']);
        $this->assertStringNotContainsString('meet.example.com', json_encode($payload));
    }

    public function testAPhysicalLocationIsTheVenue()
    {
        $event = $this->event(['location_name' => 'The Hall', 'location_locality' => 'Portland', 'location_region' => 'Oregon']);
        $payload = Discord::scheduledEventPayload($event);

        $this->assertStringStartsWith('The Hall', $payload['entity_metadata']['location']);
        $this->assertStringContainsString('Portland', $payload['entity_metadata']['location']);
    }

    public function testLongTextIsCutToDiscordsLimits()
    {
        $event = $this->event([
            'name' => str_repeat('n', 150),
            'description' => str_repeat('d', 2000),
            'location_name' => str_repeat('l', 150),
            'location_locality' => 'Portland',
        ]);
        $payload = Discord::scheduledEventPayload($event);

        $this->assertEquals(100, mb_strlen($payload['name']));
        $this->assertStringEndsWith('…', $payload['name']);
        $this->assertEquals(100, mb_strlen($payload['entity_metadata']['location']));
        $this->assertLessThanOrEqual(1000, mb_strlen($payload['description']));
        $this->assertStringEndsWith("\n\n".$event->absolute_permalink(), $payload['description']);
    }

    public function testTheHashFollowsTheCoverImageButNotItsBytes()
    {
        $event = $this->event();
        $payload = Discord::scheduledEventPayload($event);

        $hash = Discord::scheduledEventHash($event, $payload);
        $this->assertEquals($hash, Discord::scheduledEventHash($event, $payload));

        $event->cover_image = '/storage/events/cover.jpg';
        $this->assertNotEquals($hash, Discord::scheduledEventHash($event, $payload));
    }

    public function testChannelPostingStillNeedsOnlyTheChannelPermissions()
    {
        $this->assertTrue(Discord::canPost(1024 | 2048 | 16384));
        $this->assertFalse(Discord::canPost(1024 | 2048));
        $this->assertTrue((Discord::BOT_PERMISSIONS & Discord::PERMISSION_MANAGE_EVENTS) > 0);
        $this->assertTrue((Discord::BOT_PERMISSIONS & Discord::PERMISSION_CREATE_EVENTS) > 0);
        // Discord's permission bits: Manage Events is bit 33, Create Events bit 44
        $this->assertEquals(1 << 33, Discord::PERMISSION_MANAGE_EVENTS);
        $this->assertEquals(1 << 44, Discord::PERMISSION_CREATE_EVENTS);
    }
}
