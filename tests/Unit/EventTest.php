<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Event;

class EventTest extends TestCase
{
    public function testStartTimeOnly()
    {
        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->start_time = '13:00:00';

        $this->assertFalse($event->is_multiday());
        $this->assertEquals('Jan 1, 2024 1:00pm', $event->date_summary_text());
        $this->assertEquals('20240101T1300', $event->start_datetime_local());
        $this->assertEquals('2024-01-01T13:00:00+00:00', $event->start_datetime()->format('c'));
        $this->assertEquals(null, $event->end_datetime());
        $this->assertEquals('January 1, 2024', $event->display_date());
        $this->assertEquals('1:00pm', $event->display_time());
        $this->assertEquals(null, $event->duration_minutes());
    }

    public function testStartAndEndTime()
    {
        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->start_time = '13:00:00';
        $event->end_time   = '13:30:00';

        $this->assertFalse($event->is_multiday());
        $this->assertEquals('Jan 1, 2024 1:00pm', $event->date_summary_text());
        $this->assertEquals('20240101T1300', $event->start_datetime_local());
        $this->assertEquals('2024-01-01T13:00:00+00:00', $event->start_datetime()->format('c'));
        $this->assertEquals('2024-01-01T13:30:00+00:00', $event->end_datetime()->format('c'));
        $this->assertEquals('January 1, 2024', $event->display_date());
        $this->assertEquals('1:00 - 1:30pm', $event->display_time());
        $this->assertEquals(30, $event->duration_minutes());
    }

    public function testStartAndEndTimeWithTZ()
    {
        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->start_time = '13:00:00';
        $event->end_time   = '13:30:00';
        $event->timezone   = 'America/Los_Angeles';

        $this->assertFalse($event->is_multiday());
        $this->assertEquals('Jan 1, 2024 1:00pm', $event->date_summary_text());
        $this->assertEquals('20240101T1300', $event->start_datetime_local());
        $this->assertEquals('2024-01-01T13:00:00-08:00', $event->start_datetime()->format('c'));
        $this->assertEquals('2024-01-01T13:30:00-08:00', $event->end_datetime()->format('c'));
        $this->assertEquals('January 1, 2024', $event->display_date());
        $this->assertEquals('1:00 - 1:30pm', $event->display_time());
        $this->assertEquals(30, $event->duration_minutes());
    }

    public function testEndTimeIsNextDay()
    {
        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->start_time = '23:00:00';
        $event->end_time   = '01:00:00';

        $this->assertFalse($event->is_multiday());
        $this->assertEquals('Jan 1, 2024 11:00pm', $event->date_summary_text());
        $this->assertEquals('20240101T2300', $event->start_datetime_local());
        $this->assertEquals('2024-01-01T23:00:00+00:00', $event->start_datetime()->format('c'));
        $this->assertEquals('2024-01-02T01:00:00+00:00', $event->end_datetime()->format('c'));
        $this->assertEquals('January 1, 2024', $event->display_date());
        $this->assertEquals('11:00pm - 1:00am', $event->display_time());
        $this->assertEquals(120, $event->duration_minutes());
    }

    public function testEndTimeIsNextMonth()
    {
        $event = new Event;
        $event->start_date = '2024-01-31';
        $event->start_time = '23:00:00';
        $event->end_time   = '01:00:00';

        $this->assertFalse($event->is_multiday());
        $this->assertEquals('Jan 31, 2024 11:00pm', $event->date_summary_text());
        $this->assertEquals('20240131T2300', $event->start_datetime_local());
        $this->assertEquals('2024-01-31T23:00:00+00:00', $event->start_datetime()->format('c'));
        $this->assertEquals('2024-02-01T01:00:00+00:00', $event->end_datetime()->format('c'));
        $this->assertEquals('January 31, 2024', $event->display_date());
        $this->assertEquals('11:00pm - 1:00am', $event->display_time());
        $this->assertEquals(120, $event->duration_minutes());
    }

    public function testMultiday() {
        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->end_date   = '2024-01-02';

        $this->assertTrue($event->is_multiday());
        $this->assertEquals('Jan 1 - 2, 2024', $event->date_summary_text());
        $this->assertEquals('20240101T0000', $event->start_datetime_local());
        $this->assertEquals('2024-01-01T00:00:00+00:00', $event->start_datetime()->format('c'));
        $this->assertEquals(null, $event->end_datetime());
        $this->assertEquals('January 1 - 2, 2024', $event->display_date());
        $this->assertEquals('', $event->display_time());
        $this->assertEquals(1440, $event->duration_minutes());
    }

    public function testMultidayMonthCrossing() {
        $event = new Event;
        $event->start_date = '2024-01-31';
        $event->end_date   = '2024-02-02';

        $this->assertTrue($event->is_multiday());
        $this->assertEquals('Jan 31 - Feb 2, 2024', $event->date_summary_text());
        $this->assertEquals('20240131T0000', $event->start_datetime_local());
        $this->assertEquals('2024-01-31T00:00:00+00:00', $event->start_datetime()->format('c'));
        $this->assertEquals(null, $event->end_datetime());
        $this->assertEquals('January 31 - February 2, 2024', $event->display_date());
        $this->assertEquals('', $event->display_time());
        $this->assertEquals(2880, $event->duration_minutes());
    }

    public function testMultidayYearCrossing() {
        $event = new Event;
        $event->start_date = '2024-12-31';
        $event->end_date   = '2025-01-04';

        $this->assertTrue($event->is_multiday());
        $this->assertEquals('Dec 31, 2024 - Jan 4, 2025', $event->date_summary_text());
        $this->assertEquals('20241231T0000', $event->start_datetime_local());
        $this->assertEquals('2024-12-31T00:00:00+00:00', $event->start_datetime()->format('c'));
        $this->assertEquals(null, $event->end_datetime());
        $this->assertEquals('December 31, 2024 - January 4, 2025', $event->display_date());
        $this->assertEquals('', $event->display_time());
        $this->assertEquals(5760, $event->duration_minutes());
    }
}
