<?php

namespace Tests\Feature;

use App\EventRevision;
use Tests\CreatesEvents;
use Tests\TestCase;

class EventRevisionTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    public function testViewingARevisionRendersTheEventAsItWas()
    {
        $event = $this->createEvent(['description' => 'The first draft', 'tags' => 'alpha beta']);

        $revision = $event->revisions()->firstOrFail();

        $response = $this->actingAs($this->testUser())
            ->get('/event/'.$event->id.'/history/'.$revision->id);

        $response->assertOk();
        $response->assertSee('The first draft');
        $response->assertSee('alpha');
    }

    /**
     * A revision holds a snapshot of the event's own fields; responses, photos and
     * sub-events still belong to the event, so those relations have to resolve
     * through the event rather than through the revision's own id.
     */
    public function testARevisionReadsTheEventsRelations()
    {
        $event = $this->createEvent();
        $revision = $event->revisions()->firstOrFail();

        $this->assertEquals($event->responses()->count(), $revision->responses()->count());
        $this->assertEquals($event->photos()->count(), $revision->photos()->count());
        $this->assertEquals($event->children()->count(), $revision->children()->count());
        $this->assertEquals($event->tag_list, $revision->tag_list);
    }

    public function testTheDiffOfTheOldestRevisionShowsEverythingAsNew()
    {
        $event = $this->createEvent(['description' => 'Only ever version', 'tags' => 'alpha']);

        $revision = $event->revisions()->firstOrFail();

        $response = $this->actingAs($this->testUser())
            ->get('/event/'.$event->id.'/history/'.$revision->id.'/diff');

        $response->assertOk();
        $response->assertSee('Only ever version');
    }

    public function testTheDiffBetweenTwoRevisions()
    {
        $event = $this->createEvent(['description' => 'Before', 'tags' => 'alpha']);

        // Back-date the first revision so the second one has something before it
        EventRevision::where('event_id', $event->id)->update(['created_at' => now()->subMinutes(5)]);

        $this->actingAs($this->testUser())->post('/event/'.$event->id.'/save', [
            'name' => $event->name,
            'start_date' => '2032-01-02',
            'description' => 'After',
            'tags' => 'alpha gamma',
            'status' => 'confirmed',
        ])->assertRedirect();

        $revision = EventRevision::where('event_id', $event->id)->orderBy('created_at', 'desc')->firstOrFail();

        $response = $this->actingAs($this->testUser())
            ->get('/event/'.$event->id.'/history/'.$revision->id.'/diff');

        $response->assertOk();
        $response->assertSee('Before', false);
        $response->assertSee('After', false);
        $response->assertSee('gamma');
    }
}
