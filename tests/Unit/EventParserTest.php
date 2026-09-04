<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\EventParser;

class EventParserTest extends TestCase
{
    private function ics($properties) {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//EN\r\n"
            ."BEGIN:VEVENT\r\nUID:test\r\nSUMMARY:Test Event\r\n".$properties."END:VEVENT\r\n"
            ."END:VCALENDAR\r\n";
    }

    public function testEventWithTimezone()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART;TZID=America/Los_Angeles:20260401T090000\r\n"
            ."DTEND;TZID=America/Los_Angeles:20260401T103000\r\n"
        ));

        $this->assertEquals('Test Event', $event->name);
        $this->assertEquals('2026-04-01', $event->start_date);
        $this->assertEquals('09:00:00', $event->start_time);
        $this->assertEquals('10:30:00', $event->end_time);
        $this->assertEquals('America/Los_Angeles', $event->timezone);
        $this->assertFalse($event->is_multiday());
    }

    public function testQuotedAndWindowsTimezoneNames()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART;TZID=\"America/Chicago\":20260401T090000\r\n"
        ));
        $this->assertEquals('America/Chicago', $event->timezone);

        $event = EventParser::eventFromICS($this->ics(
            "DTSTART;TZID=\"Pacific Standard Time\":20260401T090000\r\n"
        ));
        $this->assertEquals('America/Los_Angeles', $event->timezone);
    }

    public function testUtcEventKeepsItsAbsoluteTime()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART:20260401T170000Z\r\nDTEND:20260401T180000Z\r\n"
        ));

        $this->assertEquals('2026-04-01', $event->start_date);
        $this->assertEquals('17:00:00', $event->start_time);
        $this->assertEquals('UTC', $event->timezone);
        $this->assertEquals('2026-04-01T17:00:00+00:00', $event->start_datetime()->format('c'));
    }

    // A UTC time says nothing about where the event is held, so a timezone hint
    // from the event or the calendar is used to show it as a local time instead
    public function testUtcEventUsesEventTimezoneHint()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART:20260401T170000Z\r\nDTEND:20260401T180000Z\r\nX-MEETING-TZ:America/Toronto\r\n"
        ));

        $this->assertEquals('13:00:00', $event->start_time);
        $this->assertEquals('14:00:00', $event->end_time);
        $this->assertEquals('America/Toronto', $event->timezone);
        $this->assertEquals('2026-04-01T13:00:00-04:00', $event->start_datetime()->format('c'));
    }

    public function testUtcEventUsesCalendarTimezoneHint()
    {
        $event = EventParser::eventFromICS(
            "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nX-WR-TIMEZONE:Europe/Berlin\r\n"
            ."BEGIN:VEVENT\r\nUID:test\r\nSUMMARY:Test Event\r\n"
            ."DTSTART:20260401T090000Z\r\nDTEND:20260401T100000Z\r\n"
            ."END:VEVENT\r\nEND:VCALENDAR\r\n"
        );

        $this->assertEquals('11:00:00', $event->start_time);
        $this->assertEquals('Europe/Berlin', $event->timezone);
    }

    // A floating time has no timezone of its own, so it is kept as written
    public function testFloatingTimeHasNoTimezone()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART:20260401T090000\r\nDTEND:20260401T100000\r\n"
        ));

        $this->assertEquals('2026-04-01', $event->start_date);
        $this->assertEquals('09:00:00', $event->start_time);
        $this->assertEquals('10:00:00', $event->end_time);
        $this->assertEmpty($event->timezone);
    }

    public function testDurationInsteadOfEndDate()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART;TZID=America/New_York:20260401T090000\r\nDURATION:PT1H30M\r\n"
        ));

        $this->assertEquals('09:00:00', $event->start_time);
        $this->assertEquals('10:30:00', $event->end_time);
    }

    public function testAllDayEvent()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART;VALUE=DATE:20260401\r\nDTEND;VALUE=DATE:20260402\r\n"
        ));

        $this->assertEquals('2026-04-01', $event->start_date);
        $this->assertEmpty($event->start_time);
        $this->assertEmpty($event->end_time);
        $this->assertEmpty($event->end_date);
        $this->assertEmpty($event->timezone);
        $this->assertFalse($event->is_multiday());
    }

    // The end date of an all-day event is exclusive, matching the ICS this app writes
    public function testMultidayAllDayEventEndsOnTheDayBeforeItsEndDate()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART;VALUE=DATE:20260401\r\nDTEND;VALUE=DATE:20260404\r\n"
        ));

        $this->assertEquals('2026-04-01', $event->start_date);
        $this->assertEquals('2026-04-03', $event->end_date);
        $this->assertEmpty($event->start_time);
        $this->assertTrue($event->is_multiday());
    }

    public function testMultidayEventWithTimes()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART;TZID=America/New_York:20260401T090000\r\n"
            ."DTEND;TZID=America/New_York:20260403T170000\r\n"
        ));

        $this->assertEquals('2026-04-01', $event->start_date);
        $this->assertEquals('09:00:00', $event->start_time);
        $this->assertEquals('2026-04-03', $event->end_date);
        $this->assertEquals('17:00:00', $event->end_time);
        $this->assertTrue($event->is_multiday());
    }

    // An end time before the start time already means the next day, so an event
    // running past midnight must not be given an end date and become multiday
    public function testEventRunningPastMidnightIsNotMultiday()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART;TZID=America/New_York:20260401T220000\r\n"
            ."DTEND;TZID=America/New_York:20260402T010000\r\n"
        ));

        $this->assertEquals('2026-04-01', $event->start_date);
        $this->assertEquals('22:00:00', $event->start_time);
        $this->assertEquals('01:00:00', $event->end_time);
        $this->assertEmpty($event->end_date);
        $this->assertFalse($event->is_multiday());
        $this->assertEquals('2026-04-02T01:00:00-04:00', $event->end_datetime()->format('c'));
    }

    public function testEventDetails()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART:20260401T170000Z\r\n"
            ."LOCATION:Congress Hall 1\r\n"
            ."URL:https://example.com/event\r\n"
            ."STATUS:CANCELLED\r\n"
            ."CATEGORIES:IETF,Working Group\r\n"
            ."DESCRIPTION:First line\\nSecond line\r\n"
        ));

        $this->assertEquals('Congress Hall 1', $event->location_name);
        $this->assertEquals('https://example.com/event', $event->website);
        $this->assertEquals('cancelled', $event->status);
        $this->assertEquals('ietf working-group', $event->temp_tag_string);
        $this->assertEquals("First line\nSecond line", $event->description);
    }

    public function testUnknownStatusIsIgnored()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART:20260401T170000Z\r\nSTATUS:NEEDS-ACTION\r\n"
        ));

        $this->assertEmpty($event->status);
    }

    // Only single events are supported, so a recurrence rule is not expanded
    public function testRecurringEventImportsTheFirstInstanceOnly()
    {
        $event = EventParser::eventFromICS($this->ics(
            "DTSTART;TZID=America/New_York:20260401T090000\r\n"
            ."DTEND;TZID=America/New_York:20260401T100000\r\n"
            ."RRULE:FREQ=WEEKLY;COUNT=5\r\n"
        ));

        $this->assertEquals('2026-04-01', $event->start_date);
        $this->assertEquals('09:00:00', $event->start_time);
    }

    public function testFeedWithSeveralEventsImportsTheOneThatStartsFirst()
    {
        $event = EventParser::eventFromICS(
            "BEGIN:VCALENDAR\r\nVERSION:2.0\r\n"
            ."BEGIN:VEVENT\r\nUID:2\r\nSUMMARY:Second\r\nDTSTART:20260402T090000Z\r\nEND:VEVENT\r\n"
            ."BEGIN:VEVENT\r\nUID:1\r\nSUMMARY:First\r\nDTSTART:20260401T090000Z\r\nEND:VEVENT\r\n"
            ."BEGIN:VEVENT\r\nUID:3\r\nSUMMARY:Third\r\nDTSTART:20260403T090000Z\r\nEND:VEVENT\r\n"
            ."END:VCALENDAR\r\n"
        );

        $this->assertEquals('First', $event->name);
        $this->assertEquals('2026-04-01', $event->start_date);
    }

    public function testCalendarWithNoEvents()
    {
        $this->assertNull(EventParser::eventFromICS(
            "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR\r\n"
        ));
    }

    public function testInvalidCalendar()
    {
        $this->assertNull(EventParser::eventFromICS("BEGIN:VCALENDAR\r\nthis is not valid\r\n"));
    }
}
