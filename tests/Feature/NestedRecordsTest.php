<?php

namespace Tests\Feature;

use App\EventRevision;
use App\Response;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * Responses and revisions are only reachable under the event they belong to.
 */
class NestedRecordsTest extends TestCase
{
    use CreatesEvents;

    private $response;

    protected function tearDown(): void
    {
        if($this->response)
            Response::withTrashed()->where('id', $this->response->id)->forceDelete();

        $this->deleteTestData();

        parent::tearDown();
    }

    public function testAResponseIsOnlyManagedThroughItsOwnEvent()
    {
        $event = $this->createEvent();
        $other = $this->createEvent();

        $this->response = new Response;
        $this->response->event_id = $event->id;
        $this->response->url = 'https://example.com/post';
        $this->response->approved = false;
        $this->response->save();

        $user = $this->testUser();
        $id = $this->response->id;

        $this->actingAs($user)->get('/event/'.$other->id.'/responses/'.$id.'.json')->assertNotFound();
        $this->actingAs($user)->post('/event/'.$other->id.'/moderate/'.$id.'/approve')->assertNotFound();
        $this->actingAs($user)->post('/event/'.$other->id.'/responses/'.$id.'/delete')->assertNotFound();

        $this->assertFalse((bool)$this->response->fresh()->approved);
        $this->assertFalse($this->response->fresh()->trashed());

        $this->actingAs($user)->get('/event/'.$event->id.'/responses/'.$id.'.json')->assertOk();
    }

    public function testARevisionIsOnlyShownUnderItsOwnEvent()
    {
        $event = $this->createEvent();
        $other = $this->createEvent();
        $revision = EventRevision::where('event_id', $event->id)->firstOrFail();

        $user = $this->testUser();

        $this->actingAs($user)->get('/event/'.$other->id.'/history/'.$revision->id)->assertNotFound();
        $this->actingAs($user)->get('/event/'.$other->id.'/history/'.$revision->id.'/diff')->assertNotFound();

        $this->actingAs($user)->get('/event/'.$event->id.'/history/'.$revision->id)->assertOk();
        $this->actingAs($user)->get('/event/'.$event->id.'/history/'.$revision->id.'/diff')->assertOk();
    }
}
