<?php

namespace Tests\Feature;

use App\Event;
use Tests\CreatesEvents;
use Tests\TestCase;

class EditEventTimesTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    /**
     * An HTML time input submits H:i. The create form already normalized that to H:i:s,
     * but the edit form stored it raw, and anything reading the time before the save
     * (such as the Zoom meeting duration) broke on the missing seconds.
     */
    public function testEditingTimesWithoutSecondsIsNormalized()
    {
        $event = $this->createEvent([
            'start_time' => '18:00',
            'end_time' => '19:00',
        ]);

        $this->assertEquals('18:00:00', $event->start_time);
        $this->assertEquals('19:00:00', $event->end_time);

        $this->actingAs($this->testUser())->post('/event/'.$event->id.'/save', [
            'name' => $event->name,
            'start_date' => '2032-01-01',
            'start_time' => '18:30',
            'end_time' => '20:00',
            'status' => 'confirmed',
        ])->assertRedirect();

        $event = Event::findOrFail($event->id);

        $this->assertEquals('18:30:00', $event->start_time);
        $this->assertEquals('20:00:00', $event->end_time);
        $this->assertEquals(90, $event->duration_minutes());
    }
}
