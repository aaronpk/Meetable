<?php

namespace Tests\Feature;

use App\Tag;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * The calendar at the top of a year page, shading each day by how many events start on it.
 */
class YearCalendarTest extends TestCase
{
    use CreatesEvents;

    // A year far enough away that nothing else in the shared test database lands on these days
    const YEAR = 2041;

    private $prefix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prefix = 'year-calendar-test-'.uniqid();
    }

    protected function tearDown(): void
    {
        $this->deleteTestData();
        Tag::where('tag', 'like', $this->prefix.'%')->delete();
        parent::tearDown();
    }

    private function dayCell($response, $date)
    {
        list($y, $m, $d) = explode('-', $date);
        $href = route('day', [$y, $m, $d]);
        if(preg_match('/<td class="([^"]*)">\s*<a href="'.preg_quote($href, '/').'"[^>]*data-tooltip="([^"]*)"/', $response->getContent(), $match))
            return ['classes' => $match[1], 'tooltip' => $match[2]];
        return null;
    }

    public function testDaysAreShadedByTheirNumberOfEvents()
    {
        $y = self::YEAR;
        $this->createEvent(['start_date' => $y.'-03-14']);
        for($i = 0; $i < 3; $i++)
            $this->createEvent(['start_date' => $y.'-03-15']);
        for($i = 0; $i < 7; $i++)
            $this->createEvent(['start_date' => $y.'-11-02']);

        $response = $this->get('/'.$y)->assertOk();
        $response->assertSee('aria-label="Events by day"', false);
        $this->assertEquals(12, substr_count($response->getContent(), 'class="month-name"'));

        $one = $this->dayCell($response, $y.'-03-14');
        $this->assertStringContainsString('has-events-1', $one['classes']);
        $this->assertEquals('1 event', $one['tooltip']);

        $three = $this->dayCell($response, $y.'-03-15');
        $this->assertStringContainsString('has-events-2', $three['classes']);
        $this->assertEquals('3 events', $three['tooltip']);

        $seven = $this->dayCell($response, $y.'-11-02');
        $this->assertStringContainsString('has-events-4', $seven['classes']);

        // A day with nothing on it is not a link
        $this->assertNull($this->dayCell($response, $y.'-03-16'));
        $response->assertDontSee('href="'.route('day', [$y, '03', '16']).'"', false);
    }

    public function testATagsYearPageCountsOnlyThatTag()
    {
        $y = self::YEAR;
        $tag = $this->prefix.'-tag';
        $this->createEvent(['start_date' => $y.'-05-20', 'tags' => $tag]);
        $this->createEvent(['start_date' => $y.'-05-20']);
        $this->createEvent(['start_date' => $y.'-05-21']);

        $response = $this->get('/'.$y.'/'.$tag)->assertOk();

        $this->assertEquals('1 event', $this->dayCell($response, $y.'-05-20')['tooltip']);
        $this->assertNull($this->dayCell($response, $y.'-05-21'));
    }

    public function testAYearPageLinksToTheNearestYearsWithEvents()
    {
        $y = self::YEAR;
        $tag = $this->prefix.'-tag';
        $this->createEvent(['start_date' => ($y - 2).'-06-01', 'tags' => $tag]);
        $this->createEvent(['start_date' => $y.'-06-01', 'tags' => $tag]);
        $this->createEvent(['start_date' => ($y + 2).'-06-01']);

        // The years in between have nothing, so the links skip them. The plain year page's
        // links carry no tags, even though the page shows a cloud of the year's tags.
        $this->get('/'.$y)->assertOk()
            ->assertSee('href="'.route('year', $y - 2).'" rel="prev"', false)
            ->assertSee('href="'.route('year', $y + 2).'" rel="next"', false)
            ->assertDontSee(route('year-tag', [$y - 2, $tag]), false);

        // On a tag's year page only that tag's years count, and the latest one has no next
        $this->get('/'.$y.'/'.$tag)->assertOk()
            ->assertSee('href="'.route('year-tag', [$y - 2, $tag]).'" rel="prev"', false)
            ->assertDontSee('rel="next"', false);
    }

    public function testTagsOnAYearPageLeadToThatTagsYearPage()
    {
        $y = self::YEAR;
        $tag = $this->prefix.'-tag';
        // The cloud only lists tags used by more than one event
        $this->createEvent(['start_date' => $y.'-04-01', 'tags' => $tag]);
        $this->createEvent(['start_date' => $y.'-04-02', 'tags' => $tag]);

        $this->get('/'.$y)->assertOk()
            ->assertSee('href="'.route('year-tag', [$y, $tag]).'"', false)
            ->assertDontSee('href="'.route('tag', $tag).'"', false);

        $this->get('/'.$y.'/04')->assertOk()
            ->assertSee('href="'.route('year-tag', [$y, $tag]).'"', false);

        // The home page keeps sending tags to their upcoming events
        $this->get('/')->assertOk()->assertDontSee('href="'.route('year-tag', [date('Y'), $tag]).'"', false);
    }

    public function testMonthAndDayPagesHaveNoCalendar()
    {
        $y = self::YEAR;
        $this->createEvent(['start_date' => $y.'-03-14']);

        $this->get('/'.$y.'/03')->assertOk()->assertDontSee('class="year-calendar"', false);
        $this->get('/'.$y.'/03/14')->assertOk()->assertDontSee('class="year-calendar"', false);
    }
}
