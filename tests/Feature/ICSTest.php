<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Event;
use Log;

class ICSTest extends TestCase
{
    public function testMultiday()
    {
        Log::info('Deleted '.Event::where('key', 'testtesttest')->forceDelete());

        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->end_date = '2024-01-02';
        $event->key = 'testtesttest';
        $event->name = 'Test Multiday';
        $event->save();

        $response = $this->withHeaders(['Accept' => '*/*'])->get('/ics/2024/01/testtesttest');
        $response->assertOk();
        $response->assertSee('BEGIN:VCALENDAR');

        $response->assertSee('DTSTART;VALUE=DATE:20240101');
        $response->assertSee('DTEND;VALUE=DATE:20240103'); // ics has end date the next day
    }

    public function testStartTimeOnly()
    {
        Log::info('Deleted '.Event::where('key', 'testtesttest')->forceDelete());

        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->start_time = '13:00:00';
        $event->key = 'testtesttest';
        $event->name = 'Test Start Time Only';
        $event->save();

        $response = $this->withHeaders(['Accept' => '*/*'])->get('/ics/2024/01/testtesttest');
        $response->assertOk();
        $response->assertSee('BEGIN:VCALENDAR');

        $response->assertSee('DTSTART:20240101T130000');
        $response->assertDontSee('DTEND');
    }

    public function testStartAndEndTime()
    {
        Log::info('Deleted '.Event::where('key', 'testtesttest')->forceDelete());

        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->start_time = '13:00:00';
        $event->end_time = '15:00:00';
        $event->key = 'testtesttest';
        $event->name = 'Test Start And End Time';
        $event->save();

        $response = $this->withHeaders(['Accept' => '*/*'])->get('/ics/2024/01/testtesttest');
        $response->assertOk();
        $response->assertSee('BEGIN:VCALENDAR');

        $response->assertSee('DTSTART:20240101T130000');
        $response->assertSee('DTEND:20240101T150000');
    }

    public function testStartAndEndTimeNextDay()
    {
        Log::info('Deleted '.Event::where('key', 'testtesttest')->forceDelete());

        $event = new Event;
        $event->start_date = '2024-01-01';
        $event->start_time = '22:00:00';
        $event->end_time = '02:00:00';
        $event->key = 'testtesttest';
        $event->name = 'Test Start And End Time';
        $event->save();

        $response = $this->withHeaders(['Accept' => '*/*'])->get('/ics/2024/01/testtesttest');
        $response->assertOk();
        $response->assertSee('BEGIN:VCALENDAR');

        $response->assertSee('DTSTART:20240101T220000');
        $response->assertSee('DTEND:20240102T020000');
    }
}
