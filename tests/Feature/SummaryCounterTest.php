<?php

namespace Tests\Feature;

use App\Event;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * The summary box is short and counts down its remaining characters.
 */
class SummaryCounterTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();
        parent::tearDown();
    }

    public function testTheEditFormHasATwoLineSummaryWithACounter()
    {
        $event = $this->createEvent(['summary' => 'A short blurb']);

        $this->actingAs($this->testUser())->get('/event/'.$event->id)
            ->assertOk()
            ->assertSee('name="summary" rows="2"', false)
            ->assertSee('data-max-length="'.Event::SUMMARY_LENGTH.'"', false)
            ->assertSee('class="summary-count"', false)
            ->assertSee('summary_no_block_tags');
    }

    public function testALongSummaryIsStillSaved()
    {
        $summary = str_repeat('x', Event::SUMMARY_LENGTH + 50).' <p>para</p> <h2>heading</h2>';
        $event = $this->createEvent(['summary' => $summary]);

        $this->assertEquals($summary, $event->fresh()->summary);
    }
}
