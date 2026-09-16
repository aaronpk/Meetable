<?php

namespace Tests\Feature;

use App\Event;
use App\Response;
use App\Helpers\Uri;
use App\Services\ExternalResponse;
use Illuminate\Support\Str;
use Tests\CreatesEvents;
use Tests\TestCase;

class EventOutputEscapingTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    public function testUnsafeSchemesAreDetected()
    {
        foreach(['javascript:alert(1)', 'JavaScript:alert(1)', " \tjava\nscript:alert(1)", 'data:text/html,<script>', 'vbscript:msgbox', "\x01javascript:alert(1)"] as $url) {
            $this->assertTrue(Uri::has_unsafe_scheme($url), json_encode($url));
            $this->assertEquals('', Uri::safe_href($url));
        }

        foreach(['https://example.com/', 'http://example.com/a:b', 'example.com/page', '/relative', '#anchor', ''] as $url) {
            $this->assertFalse(Uri::has_unsafe_scheme($url), json_encode($url));
        }
    }

    public function testAnEventNameCannotEndTheJsonLdScript()
    {
        $event = $this->createEvent([
            'name' => '</script><script>alert(1)</script>'.uniqid(),
            'start_date' => '2031-01-01',
        ]);

        $html = $this->get($event->permalink())->assertOk()->getContent();

        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $match);
        $this->assertStringNotContainsString('<', $match[1]);
        $this->assertEquals($event->name, json_decode($match[1], true)['name']);
    }

    public function testJavascriptLinksAreRejectedWhenSavingAnEvent()
    {
        $name = 'Unsafe link '.uniqid();
        $this->forgetEvent($name);

        $this->actingAs($this->testUser())
            ->post('/create', [
                'name' => $name,
                'start_date' => '2031-01-01',
                'status' => 'confirmed',
                'website' => 'javascript:alert(document.cookie)',
                'code_of_conduct_url' => 'https://example.com/coc JavaScript:alert(1)',
            ])
            ->assertSessionHasErrors(['website', 'code_of_conduct_url']);

        $this->assertEquals(0, Event::where('name', $name)->count());

        $event = $this->createEvent(['website' => 'https://example.com/']);

        $this->actingAs($this->testUser())
            ->post('/event/'.$event->id.'/save', [
                'name' => $event->name,
                'start_date' => $event->start_date,
                'status' => 'confirmed',
                'notes_url' => " java\tscript:alert(1)",
            ])
            ->assertSessionHasErrors(['notes_url']);
    }

    public function testStoredJavascriptLinksAreNotRendered()
    {
        $event = $this->createEvent(['start_date' => '2031-01-01']);

        // As if saved before links were checked
        foreach(Event::$URL_PROPERTIES as $property)
            $event->{$property} = 'javascript:alert("'.$property.'")';
        $event->save();

        $html = $this->get($event->permalink())->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('~(href|src)="\s*javascript:~i', $html);
    }

    public function testTheApiRejectsUnknownStatusesAndUnsafeLinks()
    {
        $user = $this->testUser();
        $user->api_token = Str::random(80);
        $user->save();

        $name = 'API event '.uniqid();
        $this->forgetEvent($name);

        $this->withToken($user->api_token)
            ->postJson('/api/add-event', ['name' => $name, 'start_date' => '2031-01-01', 'status' => '<SVG ONLOAD=alert(1)>'])
            ->assertStatus(400)
            ->assertJson(['error' => 'invalid status']);

        $this->withToken($user->api_token)
            ->postJson('/api/add-event', ['name' => $name, 'start_date' => '2031-01-01', 'website' => 'javascript:alert(1)'])
            ->assertStatus(400);

        $this->assertEquals(0, Event::where('name', $name)->count());
    }

    public function testAnUnknownStatusIsEscapedOnTheArchive()
    {
        $event = $this->createEvent(['start_date' => '2020-01-01']);
        $event->status = '<svg onload=alert(1)>';
        $event->save();

        $this->get('/archive')
            ->assertOk()
            ->assertDontSee('<SVG ONLOAD=ALERT(1)>', false)
            ->assertSee('&lt;SVG ONLOAD=ALERT(1)&gt;', false);
    }

    public function testResponseLinksFromOtherSitesMustBeHttp()
    {
        $response = new Response;

        ExternalResponse::setResponsePropertiesFromXRayData($response, [
            'url' => 'javascript:alert(1)',
            'author' => [
                'name' => 'Someone',
                'url' => 'javascript:alert(2)',
                'photo' => 'data:image/svg+xml,<svg onload=alert(3)>',
            ],
        ], 'https://example.com/post', 'https://events.example.com/2031/01/event');

        $this->assertNull($response->url);
        $this->assertNull($response->author_url);
        $this->assertNull($response->author_photo);
        $this->assertEquals('Someone', $response->author_name);

        // Links already stored are dropped when they're displayed
        $response->url = 'javascript:alert(1)';
        $response->source_url = null;
        $this->assertEquals('', $response->link());
        $this->assertEquals('', $response->author_url());
    }
}
