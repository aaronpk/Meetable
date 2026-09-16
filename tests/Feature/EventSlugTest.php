<?php

namespace Tests\Feature;

use App\Event;
use Tests\CreatesEvents;
use Tests\TestCase;

class EventSlugTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    public function testSlugsKeepLettersFromAnyScript()
    {
        $this->assertEquals('test-event-123', Event::slug_from_name('Test Event 123'));
        $this->assertEquals('café-société-meetup', Event::slug_from_name('Café Société Meetup'));
        $this->assertEquals('größe-straße', Event::slug_from_name('Größe & Straße'));
        $this->assertEquals('москва-встреча', Event::slug_from_name('Москва встреча'));
        $this->assertEquals('東京-meetup', Event::slug_from_name('東京 Meetup'));
        $this->assertEquals('ελληνικά', Event::slug_from_name('Ελληνικά'));
        // Vowel signs are combining marks and belong to the word
        $this->assertEquals('नमस्ते-दुनिया', Event::slug_from_name('नमस्ते दुनिया'));
        $this->assertEquals('مرحبا-بالعالم', Event::slug_from_name('مرحبا بالعالم'));
    }

    public function testSlugsHaveNoStrayHyphens()
    {
        $this->assertEquals('hola-meetup', Event::slug_from_name('¡Hola! -- Meetup?'));
        $this->assertEquals('', Event::slug_from_name('!!! ???'));
        $this->assertEquals('', Event::slug_from_name(''));
    }

    public function testTheSameNameAlwaysGetsTheSameSlug()
    {
        // "é" written as one character, and as "e" followed by a combining accent
        $this->assertEquals(Event::slug_from_name("Caf\u{00E9}"), Event::slug_from_name("Cafe\u{0301}"));
    }

    public function testEventsWithNonLatinNamesHaveWorkingUrls()
    {
        $event = $this->createEvent(['name' => 'Москва встреча '.uniqid(), 'start_date' => '2031-06-17']);
        $this->app['auth']->forgetGuards();

        $this->assertStringStartsWith('москва-встреча-', $event->slug);
        $this->assertStringStartsWith('/2031/06/'.rawurlencode('москва-встреча-'), $event->permalink());

        $this->get($event->permalink())->assertOk()->assertSee($event->name);

        // The unencoded form of the same URL
        $this->get('/2031/06/'.$event->slug.'-'.$event->key)->assertOk();

        // A URL with an old or wrong slug redirects to the event's current URL
        $this->get('/2031/06/old-slug-'.$event->key)->assertRedirect($event->permalink());
    }

    public function testAnEventWhoseNameHasNoLettersUsesItsKey()
    {
        $event = $this->createEvent(['name' => '!!! '.uniqid('', false).' ???', 'start_date' => '2031-06-17']);
        $event->name = '!!! ???';
        $event->slug = Event::slug_from_name($event->name);
        $event->save();
        $this->forgetEvent('!!! ???');
        $this->app['auth']->forgetGuards();

        $this->assertEquals('/2031/06/'.$event->key, $event->permalink());
        $this->get($event->permalink())->assertOk();
    }
}
