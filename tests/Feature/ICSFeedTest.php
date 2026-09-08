<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The .ics URLs always serve the calendar itself, whatever the browser asks for,
 * and the matching /preview URLs serve the HTML page describing the feed.
 */
class ICSFeedTest extends TestCase
{
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
