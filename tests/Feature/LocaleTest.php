<?php

namespace Tests\Feature;

use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * A site set to a language without its own translation files still shows the English
 * text, with dates in that language, and never shows a translation key.
 */
class LocaleTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        app()->setLocale('en');
        $this->deleteTestData();

        parent::tearDown();
    }

    // Anything that looks like a key from the app's language files, shown as text or in an attribute
    private function assertNoKeys($html, $page)
    {
        $groups = array_map(function($path){ return basename($path, '.php'); }, glob(resource_path('lang/en/*.php')));
        $groups = array_diff($groups, ['auth', 'pagination', 'passwords', 'validation']);

        // The JavaScript strings are listed by key name, which is expected
        $html = preg_replace('/<script.*?<\/script>/s', '', $html);

        preg_match_all('/[>"\s]((?:'.implode('|', $groups).')\.[a-z0-9_]+(?:\.[a-z0-9_]+)*)[<"\s]/', $html, $matches);

        $this->assertEquals([], $matches[1], 'Translation keys shown on '.$page);
    }

    public function testPagesInAnotherLanguageFallBackToEnglishText()
    {
        $event = $this->createEvent([
            'start_date' => '2031-06-17',
            'start_time' => '18:30',
            'end_time' => '20:00',
            'timezone' => 'UTC',
            'location_locality' => 'Portland',
            'tags' => 'locale-test-'.uniqid(),
        ]);

        app()->setLocale('fr');

        $user = $this->testUser();
        $pages = [
            $event->permalink() => null,
            '/' => null,
            '/archive' => null,
            '/event/'.$event->id => $user,
            '/new' => $user,
            '/settings' => $user,
            '/event/'.$event->id.'/history' => $user,
        ];

        foreach($pages as $url => $as) {
            $this->app['auth']->forgetGuards();
            $response = $as ? $this->actingAs($as)->get($url) : $this->get($url);
            $response->assertOk();
            $this->assertNoKeys($response->getContent(), $url);
        }

        $this->app['auth']->forgetGuards();
        $html = $this->get($event->permalink())->getContent();

        $this->assertStringContainsString('<html lang="fr">', $html);
        $this->assertStringContainsString('juin', $html);
        $this->assertStringContainsString('Add to Calendar', $html);
    }
}
