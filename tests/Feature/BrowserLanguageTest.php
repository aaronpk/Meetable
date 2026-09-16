<?php

namespace Tests\Feature;

use App\DiscordNotification;
use App\Event;
use App\Services\Discord;
use Tests\CreatesEvents;
use Tests\TestCase;

class BrowserLanguageTest extends TestCase
{
    use CreatesEvents;

    private $event;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.available_locales' => ['en', 'fr', 'de']]);

        $this->event = $this->createEvent(['start_date' => '2031-06-17']);
        $this->app['auth']->forgetGuards();
    }

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    private function pageLanguage($response)
    {
        preg_match('/<html lang="([^"]+)"/', $response->getContent(), $match);
        return $match[1];
    }

    public function testThePageIsShownInTheBrowsersPreferredLanguage()
    {
        $response = $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8')->get($this->event->permalink());

        $response->assertOk();
        $this->assertEquals('fr', $this->pageLanguage($response));
        $response->assertSee('juin 17, 2031');
        $this->assertStringContainsString('Accept-Language', $response->headers->get('Vary'));
    }

    public function testTheBrowsersLanguagesAreTriedInOrder()
    {
        $this->assertEquals('de', $this->pageLanguage($this->withHeader('Accept-Language', 'ja, de-CH;q=0.8, fr;q=0.5')->get('/')));
        $this->assertEquals('fr', $this->pageLanguage($this->withHeader('Accept-Language', 'fr-CA')->get('/')));
    }

    public function testTheSitesLanguageIsUsedWhenNothingMatches()
    {
        $this->assertEquals('en', $this->pageLanguage($this->withHeader('Accept-Language', 'ja, ko;q=0.5')->get('/')));
        $this->assertEquals('en', $this->pageLanguage($this->get('/')));
    }

    public function testAChosenLanguageOverridesTheBrowser()
    {
        $response = $this->withHeader('Accept-Language', 'fr')
            ->withHeader('Referer', url($this->event->permalink()))
            ->get('/language/de');

        $response->assertRedirect(url($this->event->permalink()));
        $response->assertCookie('locale', 'de');

        $response = $this->withHeader('Accept-Language', 'fr')->withCookie('locale', 'de')->get('/');
        $this->assertEquals('de', $this->pageLanguage($response));

        // A cookie naming a language that isn't available is ignored
        $response = $this->withHeader('Accept-Language', 'fr')->withCookie('locale', 'xx')->get('/');
        $this->assertEquals('fr', $this->pageLanguage($response));
    }

    public function testOnlyAvailableLanguagesCanBeChosen()
    {
        $this->get('/language/xx')->assertNotFound();

        $this->withHeader('Referer', 'https://evil.example/')->get('/language/fr')->assertRedirect(url('/'));
    }

    public function testTheLanguageMenuListsTheAvailableLanguages()
    {
        $html = $this->withHeader('Accept-Language', 'fr')->get('/')->getContent();

        // French and German have no translation files in the tests, so they're shown by their code
        $this->assertStringContainsString('<b lang="fr">fr</b>', $html);
        $this->assertStringContainsString('hreflang="en">English</a>', $html);
        $this->assertStringContainsString('href="'.route('set-language', 'en').'"', $html);
        $this->assertStringContainsString('href="'.route('set-language', 'de').'"', $html);

        config(['app.available_locales' => ['en']]);
        $this->assertStringNotContainsString('class="languages"', $this->get('/')->getContent());
    }

    public function testDiscordPostsUseTheSitesLanguageWhoeverSendsThem()
    {
        app()->setLocale('fr');

        $notification = new DiscordNotification;
        $notification->message = 'Hello';

        $payload = Discord::buildEventMessage($notification, Event::find($this->event->id));

        $this->assertEquals('Tuesday, June 17, 2031', $payload['embeds'][0]['fields'][0]['value']);
        $this->assertEquals('fr', app()->getLocale());
    }
}
