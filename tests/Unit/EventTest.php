<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Event;
use DateTime, DateTimeZone;

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

    public function testIsPastNextDay() {
        $event = new Event;
        $event->start_date = '2024-12-01';
        $event->start_time = '17:00:00';
        $event->end_time = '01:00:00';
        $this->assertFalse($event->is_multiday());
        $this->assertNotNull($event->end_datetime());

        $now = new DateTime('2024-12-01T14:00:00');
        $this->assertFalse($event->is_past($now));

        $now = new DateTime('2024-12-01T19:00:00');
        $this->assertFalse($event->is_past($now));

        $now = new DateTime('2024-12-02T02:00:00');
        $this->assertTrue($event->is_past($now));
    }

    public function testIsPastNoEndTime() {
        $event = new Event;
        $event->start_date = '2024-12-01';
        $event->start_time = '17:00:00';
        $this->assertFalse($event->is_multiday());
        $this->assertNull($event->end_datetime()); // because there is no end time set

        $now = new DateTime('2024-12-01T14:00:00');
        $this->assertFalse($event->is_past($now));

        $now = new DateTime('2024-12-01T17:01:00');
        $this->assertFalse($event->is_past($now));

        // No end time defaults to 30 minute sfor "is past"
        $now = new DateTime('2024-12-01T17:31:00');
        $this->assertTrue($event->is_past($now));
    }

    public function testRecurrenceEveryNWeeksInterval() {
        $event = new Event;
        $event->start_date = '2024-01-01'; // a Monday
        $event->recurrence_interval = 'weekly_n';
        $event->recurrence_interval_count = 3;

        // 'P3W' is normalized to 21 days internally
        $this->assertEquals(21, $event->recurrence_date_interval()->d);
        $this->assertEquals('Every 3 weeks on Mondays', $event->recurrence_description());
    }

    public function testRecurrenceEveryNWeeksEndWindow() {
        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->recurrence_interval = 'weekly_n';
        $event->recurrence_interval_count = 3;

        // Lookahead is count * 5 weeks = 15 weeks = 105 days ahead of "now"
        $now = new DateTime();
        $end = $event->recurrence_end_datetime();
        $this->assertEquals(105, $now->diff($end)->days);
    }

    public function testRecurrenceEveryNWeeksFallsBackToWeekly() {
        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->recurrence_interval = 'weekly_n';
        // No count set: should behave as every 1 week (7 days), not crash
        $this->assertEquals(7, $event->recurrence_date_interval()->d);
    }

    public function testWeekOfMonth() {
        // January 2026 starts on a Thursday
        $this->assertEquals(1, Event::week_of_month(new DateTime('2026-01-06'))); // 1st Tuesday
        $this->assertEquals(3, Event::week_of_month(new DateTime('2026-01-20'))); // 3rd Tuesday
        $this->assertEquals(4, Event::week_of_month(new DateTime('2026-01-28'))); // 4th Wednesday
        $this->assertEquals(5, Event::week_of_month(new DateTime('2026-01-30'))); // 5th Friday
    }

    public function testWeeksFromEndOfMonth() {
        // January 2026 has 31 days, so its Wednesdays are the 7th through the 28th
        $this->assertEquals(1, Event::weeks_from_end_of_month(new DateTime('2026-01-28')));
        $this->assertEquals(2, Event::weeks_from_end_of_month(new DateTime('2026-01-21')));
        $this->assertEquals(4, Event::weeks_from_end_of_month(new DateTime('2026-01-07')));

        // The last day of a month is always the last of its weekday
        $this->assertEquals(1, Event::weeks_from_end_of_month(new DateTime('2026-02-28')));
    }

    public function testMonthlyDayOfWeekDescriptions() {
        $event = new Event;
        $event->start_date = '2026-01-20'; // the 3rd Tuesday
        $event->recurrence_interval = 'monthly_dow';
        $this->assertEquals('Every month on the 3rd Tuesday', $event->recurrence_description());

        $event->start_date = '2026-01-28'; // the last Wednesday
        $event->recurrence_interval = 'monthly_dow_last';
        $this->assertEquals('Every month on the last Wednesday', $event->recurrence_description());

        $event->start_date = '2026-01-21'; // the second to last Wednesday
        $this->assertEquals('Every month on the 2nd last Wednesday', $event->recurrence_description());
    }

    /**
     * These schedules land a different number of days apart depending on the month,
     * so they have no fixed interval and are worked out month by month instead.
     */
    public function testMonthlyDayOfWeekHasNoFixedInterval() {
        $event = new Event;
        $event->start_date = '2026-01-20';

        $event->recurrence_interval = 'monthly_dow';
        $this->assertNull($event->recurrence_date_interval());

        $event->recurrence_interval = 'monthly_dow_last';
        $this->assertNull($event->recurrence_date_interval());
    }

    public function testTheNthWeekdayIsPickedInEachMonth() {
        $start = (new DateTime('first day of next month'))->modify('third tuesday of this month');

        $event = new Event;
        $event->start_date = $start->format('Y-m-d');
        $event->recurrence_interval = 'monthly_dow';

        $dates = $event->recurrence_dates();

        $this->assertGreaterThan(1, count($dates));
        $this->assertEquals($start->format('Y-m-d'), $dates[0]->format('Y-m-d'));

        foreach($dates as $date) {
            $this->assertEquals('Tuesday', $date->format('l'));
            $this->assertEquals(3, Event::week_of_month($date), $date->format('Y-m-d').' is not a 3rd Tuesday');
        }
    }

    public function testTheLastWeekdayIsPickedInEachMonth() {
        $start = (new DateTime('first day of next month'))->modify('last friday of this month');

        $event = new Event;
        $event->start_date = $start->format('Y-m-d');
        $event->recurrence_interval = 'monthly_dow_last';

        $dates = $event->recurrence_dates();

        $this->assertGreaterThan(1, count($dates));

        foreach($dates as $date) {
            $this->assertEquals('Friday', $date->format('l'));
            $this->assertEquals(1, Event::weeks_from_end_of_month($date), $date->format('Y-m-d').' is not a last Friday');
        }
    }

    /**
     * Only ten months in a row have a fifth Wednesday, so those months are skipped
     * rather than spilling over into the next one.
     */
    public function testMonthsWithoutAFifthWeekdayAreSkipped() {
        $event = new Event;
        $event->start_date = '2026-01-30'; // the 5th Friday of January 2026
        $event->recurrence_interval = 'monthly_dow';

        $dates = array_map(fn($d) => $d->format('Y-m-d'), $event->recurrence_dates());

        $this->assertContains('2026-01-30', $dates);
        $this->assertNotContains('2026-02-27', $dates); // February 2026 has only four Fridays
    }
    public function testAProposedEventHasNoDateYet()
    {
        $event = new Event;
        $event->is_proposed = true;
        $event->key = 'abcdefghijkl';
        $event->slug = 'game-night';
        $event->status = 'confirmed';
        $event->meeting_url = 'https://example.com/meet';

        $this->assertEquals('/proposed/game-night-abcdefghijkl', $event->permalink());
        $this->assertNull($event->ics_permalink());
        $this->assertNull($event->sort_date());
        $this->assertNull($event->start_datetime());
        $this->assertNull($event->end_datetime());
        $this->assertEquals('', $event->start_datetime_local());
        $this->assertFalse($event->is_past());
        $this->assertFalse($event->is_starting_soon());
        $this->assertFalse($event->is_ongoing());
        $this->assertFalse($event->meeting_url_is_visible());
        $this->assertEquals('Date to be decided', $event->date_summary_text());
        $this->assertEquals('Date to be decided', $event->display_date());
        $this->assertEquals('', $event->weekday());
        $this->assertEquals('', $event->mf2_date_html());
        $this->assertNull($event->duration_minutes());
        $this->assertStringContainsString('vote-yea', $event->status_tag());
        $this->assertStringContainsString('P<span class="lower">ROPOSED</span>', $event->status_tag());
        $this->assertArrayNotHasKey('startDate', json_decode($event->toGoogleJSON(), true));
    }
}
