<?php

namespace Tests\Feature;

use App\Response;
use App\ResponsePhoto;
use App\Services\ExternalResponse;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * Removing just the photos from a response, keeping its text.
 */
class RemoveResponsePhotosTest extends TestCase
{
    use CreatesEvents;

    private $responses = [];

    protected function tearDown(): void
    {
        foreach($this->responses as $response) {
            ResponsePhoto::where('response_id', $response->id)->delete();
            Response::withTrashed()->where('id', $response->id)->forceDelete();
        }

        $this->deleteTestData();

        parent::tearDown();
    }

    private function commentWithPhotos($event, $count = 2)
    {
        $response = new Response;
        $response->event_id = $event->id;
        $response->source_url = 'https://example.com/posts/'.uniqid();
        $response->url = $response->source_url;
        $response->content_text = 'A useful comment';
        $response->approved = true;
        $response->save();
        $this->responses[] = $response;

        for($i = 1; $i <= $count; $i++) {
            ResponsePhoto::create($response, [
                'source_url' => 'https://example.com/photos/'.$i.'.jpg',
                'original_url' => 'https://example.com/photos/'.$i.'.jpg',
            ]);
        }

        return $response;
    }

    public function testThePhotosGoAndTheTextStays()
    {
        $event = $this->createEvent();
        $response = $this->commentWithPhotos($event);

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/responses/'.$response->id.'/remove-photos')
            ->assertOk()
            ->assertJson(['result' => 'ok', 'response_id' => $response->id]);

        $response = $response->fresh();
        $this->assertEquals('A useful comment', $response->content_text);
        $this->assertFalse($response->trashed());
        $this->assertTrue((bool)$response->photos_removed);
        $this->assertEquals(0, $response->photos()->count());
    }

    public function testTheResponsesPageOffersItOnlyWhenThereArePhotos()
    {
        $event = $this->createEvent();
        $with = $this->commentWithPhotos($event);
        $without = $this->commentWithPhotos($event, 0);

        $page = $this->actingAs($this->testUser())->get('/event/'.$event->id.'/responses')->assertOk();
        $page->assertSee('href="'.route('remove-response-photos', [$event, $with]).'"', false);
        $page->assertDontSee('href="'.route('remove-response-photos', [$event, $without]).'"', false);
        $page->assertSee('Remove Photos');
    }

    public function testItIsOnlyReachableThroughTheResponsesOwnEvent()
    {
        $event = $this->createEvent();
        $other = $this->createEvent();
        $response = $this->commentWithPhotos($event);

        $this->actingAs($this->testUser())
            ->post('/event/'.$other->id.'/responses/'.$response->id.'/remove-photos')
            ->assertNotFound();

        $this->assertEquals(2, $response->photos()->count());
    }

    public function testAResentWebmentionDoesNotBringThePhotosBack()
    {
        $event = $this->createEvent();
        $response = $this->commentWithPhotos($event);

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/responses/'.$response->id.'/remove-photos')
            ->assertOk();

        // What the webmention handler does when the same post is received again
        $response = $response->fresh();
        ExternalResponse::setPhotoRecords($response, ['https://example.com/photos/1.jpg', 'https://example.com/photos/3.jpg']);

        $this->assertEquals(0, $response->photos()->count());
    }
}
