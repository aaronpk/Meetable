<?php

namespace Tests\Feature;

use App\Event;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * Event pages show the weekday, and give the browser what it needs to show the
 * start and end in the viewer's timezone.
 */
class EventDateDisplayTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    private function dates($start, $end = null)
    {
        $event = new Event;
        $event->start_date = $start;
        $event->end_date = $end;
        return $event;
    }

    // The <time> element for the date line on an event page
    private function dateElement($html)
    {
        preg_match('/<time [^>]*data-dateformat="dateonly"[^>]*>/', $html, $match);
        return $match[0];
    }

    private function timeElement($html)
    {
        preg_match('/<time [^>]*data-dateformat="timeonly"[^>]*>/', $html, $match);
        return $match[0] ?? null;
    }

    public function testDatesWithTheWeekday()
    {
        $this->assertEquals('Tuesday, June 17, 2031', $this->dates('2031-06-17')->display_date(true));
        $this->assertEquals('Tuesday, June 17 - Thursday, June 19, 2031', $this->dates('2031-06-17', '2031-06-19')->display_date(true));
        $this->assertEquals('Wednesday, July 30 - Saturday, August 2, 2031', $this->dates('2031-07-30', '2031-08-02')->display_date(true));
        $this->assertEquals('Tuesday, December 30, 2031 - Friday, January 2, 2032', $this->dates('2031-12-30', '2032-01-02')->display_date(true));
    }

    public function testDatesWithoutTheWeekdayAreUnchanged()
    {
        $this->assertEquals('June 17, 2031', $this->dates('2031-06-17')->display_date());
        $this->assertEquals('June 17 - 19, 2031', $this->dates('2031-06-17', '2031-06-19')->display_date());
        $this->assertEquals('July 30 - August 2, 2031', $this->dates('2031-07-30', '2031-08-02')->display_date());
        $this->assertEquals('December 30, 2031 - January 2, 2032', $this->dates('2031-12-30', '2032-01-02')->display_date());
    }

    public function testAnInPersonEventShowsTheWeekdayAndEndTime()
    {
        $event = $this->createEvent([
            'start_date' => '2031-06-17', 'start_time' => '18:30', 'end_time' => '20:00',
            'timezone' => 'America/Los_Angeles', 'location_locality' => 'Portland',
        ]);
        $this->app['auth']->forgetGuards();

        $response = $this->get($event->permalink());
        $response->assertOk()
            ->assertSee('Tuesday, June 17, 2031')
            ->assertSee('6:30 - 8:00pm');

        // The page title keeps the shorter date
        $response->assertSee('<title>'.e($event->name).' | June 17, 2031', false);
    }

    public function testAnOnlineEventGivesTheBrowserItsEndTime()
    {
        $event = $this->createEvent([
            'start_date' => '2031-06-17', 'start_time' => '23:00', 'end_time' => '01:00',
            'timezone' => 'America/Los_Angeles',
        ]);
        $this->app['auth']->forgetGuards();

        $html = $this->get($event->permalink())->assertOk()->getContent();

        foreach([$this->dateElement($html), $this->timeElement($html)] as $element) {
            $this->assertStringContainsString('event-localize-date', $element);
            $this->assertStringContainsString('is-virtual-event', $element);
            $this->assertStringContainsString('datetime="2031-06-17T23:00:00-07:00"', $element);
            // Ends after midnight, on the next day
            $this->assertStringContainsString('data-end="2031-06-18T01:00:00-07:00"', $element);
        }
    }

    public function testOnlineEventsWithoutATimeAreNotMovedIntoTheViewersTimezone()
    {
        $single = $this->createEvent(['start_date' => '2031-06-17', 'timezone' => 'Pacific/Auckland']);
        $multi = $this->createEvent(['start_date' => '2031-06-17', 'end_date' => '2031-06-19', 'timezone' => 'Pacific/Auckland']);
        $this->app['auth']->forgetGuards();

        foreach([$single, $multi] as $event) {
            $html = $this->get($event->permalink())->assertOk()->getContent();

            $this->assertStringNotContainsString('event-localize-date', $this->dateElement($html));
            $this->assertStringNotContainsString('data-end', $this->dateElement($html));
            $this->assertNull($this->timeElement($html));
        }

        $this->get($multi->permalink())->assertSee('Tuesday, June 17 - Thursday, June 19, 2031');
    }
}
