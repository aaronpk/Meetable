<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\CreatesEvents;
use Tests\TestCase;

class ApiAddResponseTest extends TestCase
{
    use CreatesEvents;

    private $previous_setting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previous_setting = $_SERVER['ALLOW_MANAGE_EVENTS'] ?? null;
    }

    protected function tearDown(): void
    {
        $_SERVER['ALLOW_MANAGE_EVENTS'] = $this->previous_setting;
        $this->deleteTestData();

        parent::tearDown();
    }

    private function addResponse($event)
    {
        $user = $this->testUser();
        if(!$user->api_token) {
            $user->api_token = Str::random(80);
            $user->save();
        }

        // A private address, so the request is refused before anything is fetched
        return $this->withToken($user->api_token)->postJson('/api/add-response', [
            'event' => $event->absolute_permalink(),
            'url' => 'http://127.0.0.1/post',
        ]);
    }

    public function testOnlyPeopleWhoCanManageEventsCanAddResponses()
    {
        $event = $this->createEvent();

        $_SERVER['ALLOW_MANAGE_EVENTS'] = 'admins';
        $this->addResponse($event)->assertForbidden();

        $_SERVER['ALLOW_MANAGE_EVENTS'] = 'users';
        $this->addResponse($event)
            ->assertStatus(400)
            ->assertJson(['error' => 'The source URL could not be fetched']);
    }
}
