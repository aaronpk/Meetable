<?php

namespace Tests\Feature;

use App\Event;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * The .ics URLs always serve the calendar itself, whatever the browser asks for,
 * and the matching /preview URLs serve the HTML page describing the feed.
 */
class ICSFeedTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    public function testTemplatesAreNotInTheFeed()
    {
        $name = 'ICS template '.uniqid();
        $this->createEvent(['name' => $name, 'start_date' => date('Y-m-d', strtotime('+7 days')), 'is_template' => 1, 'recurrence_interval' => 'weekly_dow']);

        $instances = Event::where('name', $name)->where('is_template', 0)->count();
        $this->assertGreaterThan(0, $instances);

        $ics = $this->get('/ics/events.ics')->assertOk()->getContent();

        // Only the scheduled events appear, not the template they were made from
        $this->assertEquals($instances, substr_count($ics, 'SUMMARY:'.$name));
    }

    public function testTheTagFeedFilenameOnlyContainsTheTagName()
    {
        $this->get('/ics/tag/'.rawurlencode('a"b;c').'.ics')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=events-a-b-c.ics');
    }

    public function testTheTagFeedFilenameCanUseAnyScript()
    {
        $this->get('/ics/tag/'.rawurlencode('Москва,tokyo').'.ics')
            ->assertOk()
            ->assertHeader('Content-Disposition', "attachment; filename=events-tokyo.ics; filename*=utf-8''".rawurlencode('events-москва,tokyo.ics'));

        $this->get('/ics/tag/'.rawurlencode('東京').'.ics')
            ->assertOk()
            ->assertHeader('Content-Disposition', "attachment; filename=events.ics; filename*=utf-8''".rawurlencode('events-東京.ics'));
    }

    public function testTheFeedIsAlwaysServedAsACalendar()
    {
        // The Accept header a browser sends when following a link
        $browser = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';

        foreach([$browser, 'text/calendar', '*/*'] as $accept) {
            $response = $this->withHeaders(['Accept' => $accept])->get('/ics/events.ics');

            $response->assertOk();
            $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
            $response->assertSee('BEGIN:VCALENDAR');
        }
    }

    public function testTheFeedIsAlsoServedWithoutTheExtension()
    {
        $response = $this->get('/ics/events');

        $response->assertOk();
        $response->assertSee('BEGIN:VCALENDAR');
    }

    public function testThePreviewUrlServesTheHtmlPage()
    {
        $response = $this->get('/ics/events/preview');

        $response->assertOk();
        $response->assertViewIs('ics');
        $response->assertViewHas('url', url('/ics/events'));
    }
}
