<?php

namespace Tests\Feature;

use App\Response;
use App\Setting;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * Going to an event with a code of conduct means agreeing to it first.
 */
class RsvpCodeOfConductTest extends TestCase
{
    use CreatesEvents;

    private $previous_setting;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::$cached = [];
        $this->previous_setting = Setting::value('enable_rsvps');
        Setting::set('enable_rsvps', 1);
        Setting::$cached = [];
    }

    protected function tearDown(): void
    {
        $this->deleteTestData();

        Setting::set('enable_rsvps', $this->previous_setting);
        Setting::$cached = [];

        parent::tearDown();
    }

    public function testTheRsvpButtonOpensADialogForEachCodeOfConduct()
    {
        $event = $this->createEvent([
            'code_of_conduct_url' => 'https://indieweb.org/code-of-conduct https://example.com/conduct',
        ]);

        $this->actingAs($this->testUser())->get($event->permalink())
            ->assertOk()
            ->assertSee('id="rsvp-button"', false)
            ->assertSee('id="coc-modal"', false)
            ->assertSee('This event has codes of conduct.')
            ->assertSee('href="https://indieweb.org/code-of-conduct" target="_blank">indieweb.org/code-of-conduct</a>', false)
            ->assertSee('href="https://example.com/conduct" target="_blank">example.com/conduct</a>', false)
            ->assertSee('class="button is-primary coc-confirm" disabled', false);

        $this->assertEquals(2, substr_count($this->actingAs($this->testUser())->get($event->permalink())->getContent(), 'class="coc-agree"'));
    }

    public function testAnEventWithoutACodeOfConductHasNoDialog()
    {
        $event = $this->createEvent();

        $this->actingAs($this->testUser())->get($event->permalink())
            ->assertOk()
            ->assertSee('id="rsvp-button"', false)
            ->assertDontSee('id="coc-modal"', false);
    }

    public function testSomeoneAlreadyGoingIsNotAskedAgain()
    {
        $event = $this->createEvent(['code_of_conduct_url' => 'https://example.com/conduct']);

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/rsvp', ['rsvp' => 1])
            ->assertOk();

        $this->actingAs($this->testUser())->get($event->permalink())
            ->assertOk()
            ->assertSee('id="rsvp-button" class="button is-pressed', false)
            ->assertDontSee('id="coc-modal"', false);

        Response::withTrashed()->where('event_id', $event->id)->forceDelete();
    }

    public function testASingleCodeOfConductIsWordedInTheSingular()
    {
        $event = $this->createEvent(['code_of_conduct_url' => 'https://example.com/conduct']);

        $this->actingAs($this->testUser())->get($event->permalink())
            ->assertOk()
            ->assertSee('This event has a code of conduct.');
    }
}
