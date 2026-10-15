<?php

namespace Tests\Feature;

use App\Event;
use App\Tag;
use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * The archive pages show 50 events at a time, newest first, addressed by cursors so a page keeps
 * showing the same events after new ones are added.
 */
class ArchivePaginationTest extends TestCase
{
    use CreatesEvents;

    private $prefix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prefix = 'archive-page-test-'.uniqid();
    }

    protected function tearDown(): void
    {
        $this->deleteTestData();
        Tag::where('tag', 'like', $this->prefix.'%')->delete();
        parent::tearDown();
    }

    // The URL of the page of older (rel next) or newer (rel prev) events, from the page's head
    private function linkedPage($response, $rel)
    {
        if(preg_match('/<link rel="'.$rel.'" href="([^"]+)">/', $response->getContent(), $match))
            return html_entity_decode($match[1]);
        return null;
    }

    private function namesOn($response, array $events)
    {
        return array_values(array_filter(array_map(function($event) use($response){
            return str_contains($response->getContent(), e($event->name)) ? $event->name : null;
        }, $events)));
    }

    public function testTheArchiveShowsFiftyEventsAtATimeNewestFirst()
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $events = [];
        for($i = 0; $i < 55; $i++)
            $events[] = $this->createEvent(['name' => $this->prefix.' event '.sprintf('%02d', $i), 'start_date' => $yesterday]);

        $first = $this->get('/archive')->assertOk();
        $this->assertNull($this->linkedPage($first, 'prev'));
        $older_url = $this->linkedPage($first, 'next');
        $this->assertStringContainsString('/archive?before=', $older_url);
        $first->assertSee('href="'.route('year', date('Y')).'"', false);
        $first->assertSee('Events by year');
        $first->assertSee('Older events');

        $on_first = $this->namesOn($first, $events);
        $this->assertCount(50, $on_first);

        $second = $this->get($older_url)->assertOk();
        $on_second = $this->namesOn($second, $events);
        $this->assertCount(5, $on_second);
        $this->assertEquals([], array_intersect($on_first, $on_second));
        $this->assertNotNull($this->linkedPage($second, 'prev'));

        // Newest first: everything on the first page was created after everything on the second
        $ids = function($names) use($events) {
            return array_map(fn($e) => $e->id, array_filter($events, fn($e) => in_array($e->name, $names)));
        };
        $this->assertGreaterThan(max($ids($on_second)), min($ids($on_first)));

        // A new event changes the first page but not a page that was already linked to
        $this->createEvent(['name' => $this->prefix.' late arrival', 'start_date' => $yesterday]);
        $again = $this->get($older_url)->assertOk();
        $this->assertEquals($on_second, $this->namesOn($again, $events));
        $this->assertStringNotContainsString($this->prefix.' late arrival', $again->getContent());
    }

    public function testPagesThatDoNotExistAreNotFound()
    {
        $this->get('/archive?before=nonsense')->assertNotFound();
        $this->get('/archive?before=1900-01-01T00:00:00Z,1')->assertNotFound();
        $this->get('/archive?after=2999-01-01T00:00:00Z,1')->assertNotFound();
    }

    public function testTheTagArchiveShowsTwentyEventsAtATimeWithYearsInTheHeadings()
    {
        $tag = $this->prefix.'-tag';
        $events = [];
        for($i = 0; $i < 55; $i++) {
            $date = date('Y-m-d', strtotime('2025-11-20 +'.$i.' days'));
            $events[] = $this->createEvent(['name' => $this->prefix.' tagged '.sprintf('%02d', $i), 'start_date' => $date, 'tags' => $tag]);
        }
        $names = fn($list) => array_map(fn($e) => $e->name, $list);
        $oldest = array_slice($events, 0, 15);
        $middle = array_slice($events, 15, 20);
        $newest = array_slice($events, 35);

        $first = $this->get('/tag/'.$tag.'/archive')->assertOk();
        $this->assertEquals($names($newest), $this->namesOn($first, $newest));
        $this->assertEquals([], $this->namesOn($first, array_merge($oldest, $middle)));
        $first->assertSee('January 2026')
            ->assertSee('December 2025')
            ->assertDontSee('November 2025')
            ->assertSee('href="'.route('ics-tag-preview', $tag).'"', false)
            ->assertDontSee('href="'.route('tag-archive', $tag).'"', false)
            ->assertSee('href="'.route('year-tag', [2025, $tag]).'"', false)
            ->assertSee('2025<span class="count">42</span>', false)
            ->assertSee('2026<span class="count">13</span>', false);
        $this->assertNull($this->linkedPage($first, 'prev'));

        $second = $this->get($this->linkedPage($first, 'next'))->assertOk();
        $this->assertEquals($names($middle), $this->namesOn($second, $middle));
        $this->assertEquals([], $this->namesOn($second, array_merge($oldest, $newest)));
        // Exactly a page's worth is newer, so the way back is the archive's own URL
        $this->assertEquals(url('/tag/'.$tag.'/archive'), $this->linkedPage($second, 'prev'));

        $third = $this->get($this->linkedPage($second, 'next'))->assertOk();
        $this->assertEquals($names($oldest), $this->namesOn($third, $oldest));
        $this->assertEquals([], $this->namesOn($third, array_merge($middle, $newest)));
        $third->assertSee('November 2025');
        $this->assertNull($this->linkedPage($third, 'next'));
        $this->assertStringContainsString('?after=', $this->linkedPage($third, 'prev'));

        $this->get('/tag/'.$this->prefix.'-none/archive')->assertNotFound();
    }
}
