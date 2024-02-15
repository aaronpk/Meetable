<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Event;
use DateTime, DateTimeZone;

class ZoomTest extends TestCase
{
    public function testStartTimeOnly()
    {
        $event = new Event;

        $event->current_participants += 1;
        $event->max_participants = max($event->max_participants, $event->current_participants);

        $this->assertEquals(1, $event->current_participants);
        $this->assertEquals(1, $event->max_participants);

        $event->current_participants += 1;
        $event->max_participants = max($event->max_participants, $event->current_participants);

        $this->assertEquals(2, $event->current_participants);
        $this->assertEquals(2, $event->max_participants);

        $event->current_participants -= 1;
        $this->assertEquals(1, $event->current_participants);
        $this->assertEquals(2, $event->max_participants);

    }
}
