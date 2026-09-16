<?php

namespace Tests\Feature;

use App\Event;
use App\EventDateOption;
use App\EventDateVote;
use App\EventRevision;
use App\Events\EventUpdated;
use App\Setting;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * An event proposed without a date: people vote on its candidate dates, and someone
 * who can edit the event then chooses one.
 */
class ProposedEventTest extends TestCase
{
    use CreatesEvents;

    private $previous_setting;
    private $other_users = [];

    protected function setUp(): void
    {
        parent::setUp();

        Setting::$cached = [];
        $this->previous_setting = Setting::value('enable_proposed_events');
        $this->enableProposedEvents(true);
    }

    protected function tearDown(): void
    {
        foreach($this->other_users as $user) {
            DB::table('event_date_votes')->where('user_id', $user->id)->delete();
            DB::table('user_emails')->where('user_id', $user->id)->delete();
            User::where('id', $user->id)->delete();
        }

        $this->deleteTestData();

        Setting::set('enable_proposed_events', $this->previous_setting);
        Setting::$cached = [];

        parent::tearDown();
    }

    private function enableProposedEvents(bool $enabled): void
    {
        Setting::set('enable_proposed_events', $enabled ? 1 : 0);
        Setting::$cached = [];
    }

    private function createProposal(array $fields = []): Event
    {
        return $this->createEvent(array_merge([
            'is_proposed' => 1,
            'start_date' => '',
            'timezone' => 'America/Los_Angeles',
            'options' => [
                ['date' => '2032-10-06'],
                ['date' => '2032-10-08', 'start_time' => '18:00', 'end_time' => '20:00'],
            ],
        ], $fields));
    }

    private function otherUser(): User
    {
        $user = new User;
        $user->identifier = 'voter-'.uniqid();
        $user->email = $user->identifier.'@example.com';
        $user->name = 'Other Voter';
        $user->is_admin = false;
        $user->save();

        $this->other_users[] = $user;

        return $user;
    }

    private function vote(User $user, Event $event, EventDateOption $option, $vote)
    {
        return $this->actingAs($user)
            ->postJson('/event/'.$event->id.'/vote', ['option_id' => $option->id, 'vote' => $vote]);
    }

    public function testAProposedEventHasCandidateDatesInsteadOfADate()
    {
        $event = $this->createProposal();

        $this->assertEquals(1, $event->is_proposed);
        $this->assertNull($event->start_date);
        $this->assertNull($event->sort_date);
        $this->assertEquals('confirmed', $event->status);

        $options = $event->date_options;
        $this->assertCount(2, $options);
        $this->assertEquals('2032-10-06', $options[0]->date);
        $this->assertNull($options[0]->start_time);
        $this->assertEquals('2032-10-08', $options[1]->date);
        $this->assertEquals('18:00:00', $options[1]->start_time);
        $this->assertEquals('20:00:00', $options[1]->end_time);

        $this->assertEquals('/proposed/'.$event->slug.'-'.$event->key, $event->permalink());
        $this->assertNull($event->ics_permalink());

        $this->get($event->permalink())->assertOk();

        $this->assertEquals(1, $event->revisions()->first()->is_proposed);
    }

    public function testProposingNeedsTheSettingTurnedOn()
    {
        $this->enableProposedEvents(false);

        $name = 'Test Event '.uniqid();
        $this->forgetEvent($name);

        $this->actingAs($this->testUser())
            ->post('/create', [
                'name' => $name,
                'is_proposed' => 1,
                'options' => [['date' => '2032-10-06'], ['date' => '2032-10-08']],
            ])
            ->assertForbidden();
    }

    public function testAProposalNeedsAtLeastTwoDates()
    {
        $name = 'Test Event '.uniqid();
        $this->forgetEvent($name);

        $this->actingAs($this->testUser())
            ->post('/create', [
                'name' => $name,
                'is_proposed' => 1,
                'options' => [['date' => '2032-10-06']],
            ])
            ->assertSessionHasErrors('options');

        $this->assertNull(Event::where('name', $name)->first());
    }

    public function testTheFormOffersToProposeDatesOnlyWhenEnabled()
    {
        $this->actingAs($this->testUser())->get('/new')
            ->assertOk()
            ->assertSee('name="is_proposed"', false)
            ->assertSee('id="proposed-option-template"', false);

        $this->enableProposedEvents(false);

        $this->actingAs($this->testUser())->get('/new')
            ->assertOk()
            ->assertDontSee('name="is_proposed"', false);
    }

    public function testTheEditFormListsTheProposedDatesWithTheirVotes()
    {
        $event = $this->createProposal();
        $option = $event->date_options[0];
        $this->vote($this->testUser(), $event, $option, 'yes');

        $this->actingAs($this->testUser())->get('/event/'.$event->id)
            ->assertOk()
            ->assertDontSee('name="is_proposed"', false)
            ->assertSee('choosing one of the proposed dates on the event page')
            ->assertSee('name="options[0][id]" value="'.$option->id.'"', false)
            ->assertSee('value="2032-10-08"', false)
            ->assertSee('data-votes="1"', false)
            ->assertSee('1 vote');

        // A copy starts a new poll with the same dates and no votes
        $this->actingAs($this->testUser())->get('/event/'.$event->id.'/clone')
            ->assertOk()
            ->assertSee('name="is_proposed" value="1" id="is_proposed" checked', false)
            ->assertSee('name="options[0][id]" value=""', false)
            ->assertSee('data-votes="0"', false);
    }

    public function testTheRevisionHistoryShowsARevisionFromBeforeTheDateWasChosen()
    {
        $event = $this->createProposal();
        $revision = $event->revisions()->first();

        $this->actingAs($this->testUser())
            ->get('/event/'.$event->id.'/history/'.$revision->id)
            ->assertOk()
            ->assertSee('Proposed event: vote on a date');

        $this->actingAs($this->testUser())
            ->get('/event/'.$event->id.'/history/'.$revision->id.'/diff')
            ->assertOk();
    }

    public function testVotingChangesAndClearsTheAnswer()
    {
        $event = $this->createProposal();
        $option = $event->date_options[1];

        $response = $this->vote($this->testUser(), $event, $option, 'yes');
        $response->assertOk()
            ->assertJsonPath('options.'.$option->id.'.yes', 1)
            ->assertJsonPath('options.'.$option->id.'.user_vote', 'yes')
            ->assertJsonPath('options.'.$option->id.'.voters.yes.0.name', 'Test User')
            ->assertJsonPath('leading_option_id', $option->id);

        $this->assertEquals(1, EventDateVote::where('event_date_option_id', $option->id)->count());

        $this->vote($this->testUser(), $event, $option, 'no')
            ->assertOk()
            ->assertJsonPath('options.'.$option->id.'.yes', 0)
            ->assertJsonPath('options.'.$option->id.'.no', 1);

        $this->assertEquals(1, EventDateVote::where('event_date_option_id', $option->id)->count());

        $this->vote($this->testUser(), $event, $option, '')
            ->assertOk()
            ->assertJsonPath('options.'.$option->id.'.no', 0)
            ->assertJsonPath('options.'.$option->id.'.user_vote', null)
            ->assertJsonPath('leading_option_id', null);

        $this->assertEquals(0, EventDateVote::where('event_date_option_id', $option->id)->count());
    }

    public function testTheLeadingDateIsTheOneMostPeopleCanMake()
    {
        $event = $this->createProposal();
        list($first, $second) = $event->date_options;

        $voter = $this->otherUser();

        // Two yes answers beat one yes and one if need be
        $this->vote($this->testUser(), $event, $first, 'yes');
        $this->vote($voter, $event, $first, 'ifneedbe');
        $this->vote($this->testUser(), $event, $second, 'yes');
        $response = $this->vote($voter, $event, $second, 'yes');

        $response->assertJsonPath('leading_option_id', $second->id);

        $this->assertEquals($second->id, $event->leading_date_option()->id);
        $this->assertEquals(2, $event->voter_count());
    }

    public function testVotingIsOnlyForLoggedInUsersWhileTheFeatureIsOn()
    {
        $event = $this->createProposal();
        $option = $event->date_options[0];

        $this->app['auth']->forgetGuards();
        $this->post('/event/'.$event->id.'/vote', ['option_id' => $option->id, 'vote' => 'yes'])
            ->assertRedirect();

        $this->enableProposedEvents(false);
        $this->vote($this->testUser(), $event, $option, 'yes')->assertForbidden();
    }

    public function testVotesOnAnotherEventsDateAreRejected()
    {
        $event = $this->createProposal();
        $other = $this->createProposal();

        $this->vote($this->testUser(), $event, $other->date_options[0], 'yes')->assertNotFound();
    }

    public function testEditingTheDatesKeepsVotesOnlyForUnchangedDates()
    {
        $event = $this->createProposal();
        list($first, $second) = $event->date_options;

        $this->vote($this->testUser(), $event, $first, 'yes');
        $this->vote($this->testUser(), $event, $second, 'yes');

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/save', [
                'name' => $event->name,
                'options' => [
                    // unchanged, keeps its vote
                    ['id' => $first->id, 'date' => '2032-10-06'],
                    // moved to another time, so its vote no longer applies
                    ['id' => $second->id, 'date' => '2032-10-08', 'start_time' => '19:00', 'end_time' => '20:00'],
                    ['date' => '2032-10-10'],
                ],
            ])
            ->assertRedirect($event->permalink());

        $event = $event->fresh();
        $this->assertNull($event->start_date);
        $this->assertEquals(1, $event->is_proposed);

        $options = $event->date_options;
        $this->assertCount(3, $options);
        $this->assertEquals(1, $options[0]->votes()->count());
        $this->assertEquals('19:00:00', $options[1]->start_time);
        $this->assertEquals(0, $options[1]->votes()->count());
        $this->assertEquals('2032-10-10', $options[2]->date);

        // Leaving a date out of the form removes it and its votes
        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/save', [
                'name' => $event->name,
                'options' => [
                    ['id' => $second->id, 'date' => '2032-10-08'],
                    ['id' => $options[2]->id, 'date' => '2032-10-10'],
                ],
            ])
            ->assertRedirect();

        $this->assertNull(EventDateOption::find($first->id));
        $this->assertEquals(0, EventDateVote::where('event_date_option_id', $first->id)->count());
        $this->assertCount(2, $event->fresh()->date_options);
    }

    public function testChoosingADateSchedulesTheEvent()
    {
        $event = $this->createProposal();
        $option = $event->date_options[1];
        $proposed_url = $event->permalink();

        $this->vote($this->testUser(), $event, $option, 'yes');

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/finalize', ['option_id' => $option->id])
            ->assertRedirect('/2032/10/'.$event->slug.'-'.$event->key);

        $event = $event->fresh();
        $this->assertEquals(0, $event->is_proposed);
        $this->assertEquals('2032-10-08', $event->start_date);
        $this->assertEquals('18:00:00', $event->start_time);
        $this->assertEquals('20:00:00', $event->end_time);
        $this->assertNull($event->end_date);
        $this->assertNotNull($event->sort_date);
        $this->assertEquals($option->id, $event->chosen_date_option_id);

        // Both revisions can land in the same second, so order them by id
        $revisions = $event->revisions()->reorder('id', 'desc')->get();
        $this->assertEquals(0, $revisions[0]->is_proposed);
        $this->assertEquals('Scheduled for October 8, 2032', $revisions[0]->edit_summary);
        $this->assertEquals(1, $revisions[1]->is_proposed);

        EventFacade::assertDispatched(EventUpdated::class);

        // The vote is kept for the record
        $this->assertCount(2, $event->date_options);
        $this->assertEquals(1, EventDateVote::where('event_date_option_id', $option->id)->count());

        // The old URL now points at the scheduled event, and voting is over
        $this->get($proposed_url)->assertRedirect($event->permalink());
        $this->vote($this->testUser(), $event, $option, 'no')->assertNotFound();
        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/finalize', ['option_id' => $option->id])
            ->assertNotFound();
    }

    public function testAProposedDateCanSpanSeveralDays()
    {
        $event = $this->createProposal([
            'options' => [
                // The times are dropped, like on a scheduled multi-day event
                ['date' => '2032-10-06', 'end_date' => '2032-10-08', 'start_time' => '18:00', 'end_time' => '20:00'],
                ['date' => '2032-10-10'],
            ],
        ]);

        $option = $event->date_options[0];
        $this->assertEquals('2032-10-08', $option->end_date);
        $this->assertNull($option->start_time);
        $this->assertNull($option->end_time);
        $this->assertTrue($option->is_multiday());

        $this->get($event->permalink())->assertOk()->assertSee('Wednesday, October 6 - Friday, October 8, 2032');

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/finalize', ['option_id' => $option->id])
            ->assertRedirect('/2032/10/'.$event->slug.'-'.$event->key);

        $event = $event->fresh();
        $this->assertEquals('2032-10-06', $event->start_date);
        $this->assertEquals('2032-10-08', $event->end_date);
        $this->assertNull($event->start_time);
        $this->assertTrue($event->is_multiday());
    }

    public function testAnEndDateCannotBeBeforeTheDate()
    {
        $name = 'Test Event '.uniqid();
        $this->forgetEvent($name);

        $this->actingAs($this->testUser())
            ->post('/create', [
                'name' => $name,
                'is_proposed' => 1,
                'options' => [['date' => '2032-10-06', 'end_date' => '2032-10-05'], ['date' => '2032-10-10']],
            ])
            ->assertSessionHasErrors('options.0.end_date');

        $this->assertNull(Event::where('name', $name)->first());
    }

    public function testChoosingADateNeedsOneOfTheProposedDates()
    {
        $event = $this->createProposal();
        $other = $this->createProposal();

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/finalize', ['option_id' => $other->date_options[0]->id])
            ->assertSessionHasErrors();

        $this->assertEquals(1, $event->fresh()->is_proposed);
    }

    public function testDatedUrlsOfAProposalRedirectToItsProposedUrl()
    {
        $event = $this->createProposal();

        $this->get('/'.$event->key)->assertRedirect($event->permalink());
        $this->get('/2032/10/'.$event->slug.'-'.$event->key)->assertRedirect($event->permalink());
        $this->get('/proposed/'.$event->key)->assertRedirect($event->permalink());
        $this->get('/proposed/wrong-slug-'.$event->key)->assertRedirect($event->permalink());
    }

    public function testAProposalIsListedUnderProposedEventsAndNowhereElse()
    {
        $event = $this->createProposal();

        $this->get('/proposed')->assertOk()
            ->assertSee($event->name)
            ->assertSee('2 possible dates');

        $this->get('/')->assertOk()
            ->assertDontSee($event->name)
            ->assertSee('1 proposed event is looking for a date');

        $this->get('/ics/events.ics')->assertOk()->assertDontSee($event->name);

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/finalize', ['option_id' => $event->date_options[0]->id]);

        $this->get('/proposed')->assertOk()->assertDontSee($event->name);
        $this->get('/')->assertOk()->assertSee($event->name);
    }

    public function testAnUnlistedProposalStaysOffTheUnlistedPage()
    {
        $event = $this->createProposal(['unlisted' => 1]);

        $this->actingAs($this->testUser())->get('/events/unlisted')
            ->assertOk()
            ->assertDontSee($event->name);

        $this->get('/proposed')->assertOk()->assertDontSee($event->name);
    }

    public function testTheProposedListNeedsTheSettingTurnedOn()
    {
        $this->enableProposedEvents(false);

        $this->get('/proposed')->assertNotFound();
    }

    public function testTheEventPageShowsTheDatesToVoteOn()
    {
        $event = $this->createProposal();
        $this->vote($this->testUser(), $event, $event->date_options[0], 'ifneedbe');

        $response = $this->actingAs($this->testUser())->get($event->permalink());
        $response->assertOk()
            ->assertSee('Wednesday, October 6, 2032')
            ->assertSee('Friday, October 8, 2032')
            ->assertSee('6:00 - 8:00pm')
            ->assertSee('Times are in America/Los_Angeles')
            ->assertSee('data-vote="yes"', false)
            ->assertSee('vote-button is-ifneedbe is-pressed', false)
            ->assertSee('Schedule this date')
            ->assertDontSee('Add to Calendar')
            ->assertDontSee('id="rsvps"', false);

        $this->app['auth']->forgetGuards();
        $this->get($event->permalink())
            ->assertOk()
            ->assertSee('Log in to vote')
            ->assertDontSee('data-vote=', false)
            ->assertDontSee('Schedule this date');
    }

    public function testAScheduledEventShowsHowItsDateWasChosen()
    {
        $event = $this->createProposal();
        $option = $event->date_options[0];
        $this->vote($this->testUser(), $event, $option, 'yes');

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/finalize', ['option_id' => $option->id]);

        $this->get($event->fresh()->permalink())
            ->assertOk()
            ->assertSee('The date was chosen from 2 proposed dates')
            ->assertSee('is-chosen', false)
            ->assertSee('Add to Calendar')
            ->assertDontSee('data-vote=', false);
    }
}
