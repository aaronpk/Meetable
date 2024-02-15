<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ICSContentNegotiationTest extends TestCase
{
    public function testContentNegotiation()
    {
        // Request with default header (text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8) results in the ICS view, HTML.
        $response = $this->get('/ics/events.ics');
        $response->assertViewIs('ics');
        // Request with specific text/calendar header results in the ICS file.
        $response = $this->withHeaders(['Accept' => 'text/calendar'])->get('/ics/events.ics');
        $response->assertOk();
        $response->assertSee('BEGIN:VCALENDAR');
        // Request accepting anything results in the ICS file.
        $response = $this->withHeaders(['Accept' => '*/*'])->get('/ics/events.ics');
        $response->assertOk();
        $response->assertSee('BEGIN:VCALENDAR');
    }
}
