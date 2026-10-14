<?php

namespace Tests\Feature;

use App\Event;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * Deleting an event from the menu on its page, after a confirmation dialog.
 */
class DeleteEventTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    public function testTheEventPageMenuOffersToDeleteWithAConfirmation()
    {
        $event = $this->createEvent();

        $this->actingAs($this->testUser())->get($event->permalink())
            ->assertOk()
            ->assertSee('class="dropdown-item delete-event has-text-danger" href="'.route('delete-event', $event).'"', false)
            ->assertSee('id="delete-modal"', false)
            ->assertSee('Delete this event?')
            ->assertSee('Delete <b>'.e($event->name).'</b>?', false);
    }

    public function testTheEditFormNoLongerHasTheDeleteMenu()
    {
        $event = $this->createEvent();

        $this->actingAs($this->testUser())->get('/event/'.$event->id)
            ->assertOk()
            ->assertDontSee('delete-event', false);
    }

    public function testOnlyPeopleWhoCanEditSeeTheDeleteOption()
    {
        $event = $this->createEvent();

        $this->app['auth']->forgetGuards();
        $this->get($event->permalink())
            ->assertOk()
            ->assertDontSee('delete-event', false)
            ->assertDontSee('id="delete-modal"', false);
    }

    public function testDeletingHidesTheEvent()
    {
        $event = $this->createEvent();

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/delete')
            ->assertRedirect('/');

        $this->assertNull(Event::find($event->id));
        $this->assertNotNull(Event::withTrashed()->find($event->id));
    }
}
