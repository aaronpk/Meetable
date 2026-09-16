<?php

namespace Tests\Feature;

use App\Tag;
use Tests\CreatesEvents;
use Tests\TestCase;

class TagPagesTest extends TestCase
{
    use CreatesEvents;

    private $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prefix = 'tag-page-test-'.uniqid();
    }

    protected function tearDown(): void
    {
        $this->deleteTestData();

        Tag::where('tag', 'like', $this->prefix.'%')->delete();

        parent::tearDown();
    }

    public function testVisitingTagPagesDoesNotCreateTags()
    {
        $a = $this->prefix.'-a';
        $b = $this->prefix.'-b';

        $this->get('/tag/'.$a)->assertOk()->assertSee('#'.$a);
        $this->get('/tag/'.$a.','.$b)->assertOk();
        $this->get('/tag/'.$a.','.$b.'/archive')->assertNotFound();
        $this->get('/2031/'.$a)->assertOk();
        $this->get('/ics/tag/'.$a.'.ics')->assertOk();
        $this->get('/ics/tag/'.$a.','.$b)->assertOk();

        $this->assertEquals(0, Tag::where('tag', 'like', $this->prefix.'%')->count());
    }

    public function testTagPagesStillShowTaggedEvents()
    {
        $tag = $this->prefix.'-real';
        $event = $this->createEvent(['start_date' => '2031-06-01', 'tags' => $tag]);

        $this->get('/tag/'.strtoupper($tag))->assertOk()->assertSee($event->name);
        $this->get('/tag/'.$tag.'/archive')->assertOk()->assertSee($event->name);
        $this->get('/2031/'.$tag)->assertOk()->assertSee($event->name);
        $this->get('/ics/tag/'.$tag.'.ics')->assertOk()->assertSee($event->name);

        $this->assertEquals(1, Tag::where('tag', 'like', $this->prefix.'%')->count());
    }

    public function testLookupDoesNotSaveMissingTags()
    {
        $tag = Tag::lookup(' '.strtoupper($this->prefix).'-Missing, ');

        $this->assertFalse($tag->exists);
        $this->assertEquals($this->prefix.'-missing', $tag->tag);
        $this->assertEquals(0, Tag::where('tag', 'like', $this->prefix.'%')->count());
    }
}
