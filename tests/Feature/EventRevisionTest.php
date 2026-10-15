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

    /**
     * Any two revisions can be compared, like a page history on a wiki, so a run of small
     * edits can be seen as one change.
     */
    public function testAnyTwoRevisionsCanBeCompared()
    {
        $event = $this->createEvent(['description' => 'First', 'tags' => 'alpha']);
        EventRevision::where('event_id', $event->id)->update(['created_at' => now()->subMinutes(10)]);

        $this->actingAs($this->testUser())->post('/event/'.$event->id.'/save', [
            'name' => $event->name, 'start_date' => '2032-01-01', 'description' => 'Second', 'tags' => 'alpha', 'status' => 'confirmed',
        ])->assertRedirect();
        EventRevision::where('event_id', $event->id)->where('created_at', '>', now()->subMinute())->update(['created_at' => now()->subMinutes(5)]);

        $this->actingAs($this->testUser())->post('/event/'.$event->id.'/save', [
            'name' => $event->name.' renamed', 'start_date' => '2032-01-01', 'description' => 'Second', 'tags' => 'alpha gamma', 'status' => 'confirmed',
        ])->assertRedirect();

        $revisions = EventRevision::where('event_id', $event->id)->orderBy('created_at')->get();
        $this->assertCount(3, $revisions);
        list($oldest, $middle, $newest) = $revisions;

        // Across the whole range every field that changed anywhere shows up
        $page = $this->actingAs($this->testUser())
            ->get('/event/'.$event->id.'/history/compare?from='.$oldest->id.'&to='.$newest->id)
            ->assertOk();
        $page->assertSee('First', false)->assertSee('Second', false)->assertSee('renamed')->assertSee('gamma');
        $page->assertSee('3 changes between the revisions of');

        // Picked the other way round, the older one is still the starting point
        $this->actingAs($this->testUser())
            ->get('/event/'.$event->id.'/history/compare?from='.$newest->id.'&to='.$oldest->id)
            ->assertOk()
            ->assertSee('3 changes between the revisions of');

        // A narrower range leaves out what changed before it
        $this->actingAs($this->testUser())
            ->get('/event/'.$event->id.'/history/compare?from='.$middle->id.'&to='.$newest->id)
            ->assertOk()
            ->assertSee('2 changes between the revisions of')
            ->assertDontSee('First', false);

        $other = $this->createEvent();
        $this->actingAs($this->testUser())
            ->get('/event/'.$other->id.'/history/compare?from='.$oldest->id.'&to='.$newest->id)
            ->assertNotFound();
    }

    public function testTheHistoryPageLetsTwoRevisionsBePicked()
    {
        $event = $this->createEvent();
        EventRevision::where('event_id', $event->id)->update(['created_at' => now()->subMinutes(5)]);
        $this->actingAs($this->testUser())->post('/event/'.$event->id.'/save', [
            'name' => $event->name, 'start_date' => '2032-01-02', 'status' => 'confirmed',
        ])->assertRedirect();

        list($older, $newer) = EventRevision::where('event_id', $event->id)->orderBy('created_at')->get();

        $this->actingAs($this->testUser())->get('/event/'.$event->id.'/history')
            ->assertOk()
            ->assertSee('action="'.route('compare-revisions', $event).'"', false)
            ->assertSee('Compare selected revisions')
            ->assertSee('name="to" value="'.$newer->id.'" checked', false)
            ->assertSee('name="from" value="'.$older->id.'" checked', false);
    }
}
