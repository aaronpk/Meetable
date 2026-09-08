<?php

namespace Tests\Feature;

use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * The routes behind the "auth" middleware should send a signed-out visitor to the
 * login page, and answer an API client with a 401 rather than a redirect.
 */
class AuthenticationTest extends TestCase
{
    use CreatesEvents;

    const PROTECTED_URLS = [
        '/new',
        '/import',
        '/moderate',
        '/profile',
        '/settings',
        '/events/unlisted',
        '/events/templates',
    ];

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    public function testAGuestIsSentToTheLoginPage()
    {
        foreach(self::PROTECTED_URLS as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function testAGuestGetsA401OnApiRequests()
    {
        $this->getJson('/api/user')->assertUnauthorized();

        foreach(self::PROTECTED_URLS as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
    }

    /**
     * The timezone lookup sits behind the same middleware as the rest of the
     * event editing endpoints and should not be reachable without signing in.
     */
    public function testTheTimezoneLookupRequiresSigningIn()
    {
        $this->post('/event/timezone', ['latitude' => 45.5, 'longitude' => -122.6])
            ->assertRedirect(route('login'));
    }

    public function testASignedInUserReachesThoseRoutes()
    {
        foreach(self::PROTECTED_URLS as $url) {
            $this->actingAs($this->testUser())->get($url)->assertOk();
        }
    }
}
