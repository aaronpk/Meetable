<?php

namespace Tests\Feature;

use App\Event;
use App\Response;
use App\Tag;
use DateTime;
use Tests\CreatesEvents;
use Tests\TestCase;

class RecurringEventTest extends TestCase
{
    use CreatesEvents;

    private $tag_prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tag_prefix = 'rt'.uniqid();
    }

    protected function tearDown(): void
    {
        $ids = Event::withTrashed()->whereIn('name', $this->test_event_names)->pluck('id');
        Response::withTrashed()->whereIn('event_id', $ids)->forceDelete();

        $this->deleteTestData();

        Tag::where('tag', 'like', $this->tag_prefix.'%')->delete();

        parent::tearDown();
    }

    public function testATemplateSchedulesTheNthWeekdayOfEachMonth()
    {
        $start = (new DateTime('first day of next month'))->modify('third tuesday of this month');

        $template = $this->createTemplate($start, 'monthly_dow');

        $this->assertEquals('Every month on the 3rd Tuesday', $template->recurrence_description());

        $instances = $this->instancesOf($template);
        $this->assertGreaterThan(1, count($instances));

        foreach($instances as $instance) {
            $date = new DateTime($instance->start_date);

            $this->assertEquals('Tuesday', $date->format('l'));
            $this->assertEquals(3, Event::week_of_month($date));
        }
    }

    public function testATemplateSchedulesTheLastWeekdayOfEachMonth()
    {
        $start = (new DateTime('first day of next month'))->modify('last thursday of this month');

        $template = $this->createTemplate($start, 'monthly_dow_last');

        $this->assertEquals('Every month on the last Thursday', $template->recurrence_description());

        $instances = $this->instancesOf($template);
        $this->assertGreaterThan(1, count($instances));

        foreach($instances as $instance) {
            $date = new DateTime($instance->start_date);

            $this->assertEquals('Thursday', $date->format('l'));
            $this->assertEquals(1, Event::weeks_from_end_of_month($date));
        }
    }

    public function testTheScheduleMenuOffersTheDayOfWeekOptions()
    {
        $event = $this->createEvent();

        // The 21st of a 31 day month is both the third Wednesday and the second to last
        $response = $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/recurring/details', ['date' => '2026-01-21']);

        $response->assertOk();
        $response->assertSee('value="monthly_dow"', false);
        $response->assertSee('Every Month on the 3rd Wednesday');
        $response->assertSee('value="monthly_dow_last"', false);
        $response->assertSee('Every Month on the 2nd last Wednesday');
    }

    /**
     * Counting back from the end only reads well for the last two weeks, so a date
     * early in the month is offered forwards only.
     */
    public function testTheMenuHidesTheFromTheEndOptionEarlyInTheMonth()
    {
        $event = $this->createEvent();

        $response = $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/recurring/details', ['date' => '2026-01-06']);

        $response->assertOk();
        $response->assertSee('Every Month on the 1st Tuesday');
        $response->assertDontSee('value="monthly_dow_last"', false);
    }

    public function testAnUnknownScheduleIsRejected()
    {
        $this->actingAs($this->testUser())
            ->post('/create', [
                'name' => 'Test Event '.uniqid(),
                'start_date' => '2032-01-01',
                'status' => 'confirmed',
                'is_template' => 1,
                'recurrence_interval' => 'every_blue_moon',
            ])
            ->assertSessionHasErrors('recurrence_interval');
    }

    public function testADeletedOccurrenceIsNotCreatedAgain()
    {
        $template = $this->createTemplate(new DateTime('+1 day'), 'weekly_dow');
        $occurrence = $this->instancesOf($template)[1];
        $date = $occurrence->start_date;
        $count = count($this->instancesOf($template));

        $this->actingAs($this->testUser())
            ->post('/event/'.$occurrence->id.'/delete')
            ->assertRedirect();

        $this->artisan('recurring:schedule')->assertExitCode(0);

        $this->assertCount($count - 1, $this->instancesOf($template));
        $this->assertEquals(0, Event::where('created_from_template_event_id', $template->id)->where('start_date', $date)->count());
    }

    public function testAMovedOccurrenceIsNotDuplicated()
    {
        $template = $this->createTemplate(new DateTime('+1 day'), 'weekly_dow');
        $occurrence = $this->instancesOf($template)[1];
        $scheduled_date = $occurrence->start_date;
        $count = count($this->instancesOf($template));

        $this->actingAs($this->testUser())
            ->post('/event/'.$occurrence->id.'/save', [
                'name' => $occurrence->name,
                'start_date' => (new DateTime($scheduled_date))->modify('+1 day')->format('Y-m-d'),
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        $this->artisan('recurring:schedule')->assertExitCode(0);

        $this->assertCount($count, $this->instancesOf($template));
        $this->assertEquals(0, Event::where('created_from_template_event_id', $template->id)->where('start_date', $scheduled_date)->count());
    }

    public function testMultiDayOccurrencesKeepTheTemplatesLength()
    {
        $start = new DateTime('+1 day');
        $template = $this->createTemplate($start, 'weekly_dow', [
            'end_date' => (clone $start)->modify('+1 day')->format('Y-m-d'),
            'description' => 'Agenda: https://example.com/agenda/'.$start->format('Y-m-d'),
        ]);

        $occurrences = $this->instancesOf($template);
        $this->assertGreaterThan(1, count($occurrences));

        foreach($occurrences as $occurrence) {
            $this->assertEquals((new DateTime($occurrence->start_date))->modify('+1 day')->format('Y-m-d'), $occurrence->end_date);
            $this->assertEquals('Agenda: https://example.com/agenda/'.$occurrence->start_date, $occurrence->description);
        }
    }

    private function weeklyTemplate(array $fields = []): Event
    {
        return $this->createTemplate(new DateTime('+1 day'), 'weekly_dow', array_merge([
            'start_time' => '18:00',
            'timezone' => 'UTC',
            'description' => 'Weekly meetup',
            'tags' => $this->tag_prefix.'-a',
        ], $fields));
    }

    // Saves the template through the edit form, keeping the fields that aren't given
    private function saveTemplate(Event $template, array $fields): Event
    {
        $this->actingAs($this->testUser())
            ->post('/event/'.$template->id.'/save', array_merge([
                'name' => $template->name,
                'start_date' => $template->start_date,
                'start_time' => $template->start_time ? substr($template->start_time, 0, 5) : null,
                'timezone' => $template->timezone,
                'status' => $template->status,
                'description' => $template->description,
                'recurrence_interval' => $template->recurrence_interval,
                'tags' => implode(' ', $template->tags()->pluck('tag')->all()),
            ], $fields))
            ->assertRedirect(route('templates'));

        return $template->fresh();
    }

    private function rsvp(Event $event): Response
    {
        $response = new Response;
        $response->event_id = $event->id;
        $response->url = 'https://example.com/rsvp/'.uniqid();
        $response->rsvp = 'yes';
        $response->approved = true;
        $response->save();
        return $response;
    }

    public function testEditingATemplateUpdatesItsOccurrencesInPlace()
    {
        $template = $this->weeklyTemplate();
        $before = $this->instancesOf($template);
        $this->assertGreaterThan(1, count($before));

        $rsvp = $this->rsvp($before[0]);

        $this->saveTemplate($template, ['description' => 'Weekly meetup, now with snacks', 'start_time' => '19:30']);

        $after = $this->instancesOf($template);
        $this->assertEquals($before->pluck('id')->all(), $after->pluck('id')->all());
        $this->assertEquals($before->pluck('key')->all(), $after->pluck('key')->all());

        foreach($after as $occurrence) {
            $this->assertEquals('Weekly meetup, now with snacks', $occurrence->description);
            $this->assertEquals('19:30:00', $occurrence->start_time);
            $this->assertEquals($occurrence->start_date.' 19:30:00', $occurrence->sort_date);
        }

        $this->assertEquals($before[0]->id, $rsvp->fresh()->event_id);
        $this->assertEquals(1, $after[0]->rsvps()->count());
    }

    public function testPropertiesEditedOnAnOccurrenceAreKept()
    {
        $template = $this->weeklyTemplate();
        $customized = $this->instancesOf($template)[1];
        $customized->description = 'Special guest speaker this week';
        $customized->save();

        $this->saveTemplate($template, ['description' => 'Weekly meetup, now with snacks', 'start_time' => '19:30']);

        $occurrences = $this->instancesOf($template);
        $this->assertEquals('Weekly meetup, now with snacks', $occurrences[0]->description);
        $this->assertEquals('Special guest speaker this week', $occurrences[1]->description);
        $this->assertEquals('19:30:00', $occurrences[1]->start_time);
    }

    public function testTagChangesSkipOccurrencesWithTheirOwnTags()
    {
        $template = $this->weeklyTemplate();
        $customized = $this->instancesOf($template)[1];
        $customized->tags()->sync([Tag::get($this->tag_prefix.'-custom')->id]);

        $this->saveTemplate($template, ['tags' => $this->tag_prefix.'-a '.$this->tag_prefix.'-b']);

        $occurrences = $this->instancesOf($template);
        $this->assertEquals([$this->tag_prefix.'-a', $this->tag_prefix.'-b'], $occurrences[0]->tags()->pluck('tag')->sort()->values()->all());
        $this->assertEquals([$this->tag_prefix.'-custom'], $occurrences[1]->tags()->pluck('tag')->all());
    }

    public function testChangingTheScheduleReplacesOccurrencesThatAreNoLongerOnIt()
    {
        $template = $this->weeklyTemplate();
        $original = $this->instancesOf($template);
        $original_dates = $original->pluck('start_date')->all();
        $this->rsvp($original[0]);

        // Move the series one day later in the week
        $next_day = (new DateTime($template->start_date))->modify('+1 day')->format('Y-m-d');
        $template = $this->saveTemplate($template, ['start_date' => $next_day]);

        $moved = $this->instancesOf($template);
        $this->assertGreaterThan(0, count($moved));
        $this->assertEmpty(array_intersect($original_dates, $moved->pluck('start_date')->all()));
        foreach($moved as $occurrence) {
            $this->assertEquals((new DateTime($next_day))->format('l'), (new DateTime($occurrence->start_date))->format('l'));
        }
        $this->assertTrue(Event::withTrashed()->find($original[0]->id)->trashed());

        // And back again, which schedules fresh occurrences on the original dates
        $template = $this->saveTemplate($template, ['start_date' => $original_dates[0]]);

        $back = $this->instancesOf($template);
        $this->assertEquals($original_dates, $back->pluck('start_date')->all());
        $this->assertEmpty(array_intersect($original->pluck('id')->all(), $back->pluck('id')->all()));
    }

    public function testAnOccurrenceDeletedByHandStaysDeletedWhenTheTemplateIsSaved()
    {
        $template = $this->weeklyTemplate();
        $occurrences = $this->instancesOf($template);

        $this->actingAs($this->testUser())
            ->post('/event/'.$occurrences[1]->id.'/delete')
            ->assertRedirect();

        $this->saveTemplate($template, ['description' => 'Weekly meetup, now with snacks']);

        $this->assertEquals(
            $occurrences->pluck('id')->forget(1)->values()->all(),
            $this->instancesOf($template)->pluck('id')->all()
        );
    }

    private function createTemplate(DateTime $start, string $interval, array $fields = []): Event
    {
        $name = 'Test Recurring '.uniqid();
        $this->forgetEvent($name);

        $this->actingAs($this->testUser())
            ->post('/create', array_merge([
                'name' => $name,
                'start_date' => $start->format('Y-m-d'),
                'status' => 'confirmed',
                'is_template' => 1,
                'recurrence_interval' => $interval,
            ], $fields))
            ->assertRedirect(route('templates'));

        return Event::where('name', $name)->where('is_template', 1)->firstOrFail();
    }

    private function instancesOf(Event $template)
    {
        return Event::where('created_from_template_event_id', $template->id)
            ->orderBy('start_date')
            ->get();
    }
}
