<?php

namespace Tests\Feature;

use App\Event;
use DateTime;
use Tests\CreatesEvents;
use Tests\TestCase;

class RecurringEventTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

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

    private function createTemplate(DateTime $start, string $interval): Event
    {
        $name = 'Test Recurring '.uniqid();
        $this->forgetEvent($name);

        $this->actingAs($this->testUser())
            ->post('/create', [
                'name' => $name,
                'start_date' => $start->format('Y-m-d'),
                'status' => 'confirmed',
                'is_template' => 1,
                'recurrence_interval' => $interval,
            ])
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
