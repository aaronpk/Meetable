<?php

namespace Tests\Feature;

use Tests\CreatesEvents;
use Tests\TestCase;

/**
 * /{year}/{month}/{partial slug} finds events whose slug starts with the given text.
 */
class PartialSlugTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    public function testUnlistedEventsAreNotFound()
    {
        $prefix = 'zq'.uniqid();
        $this->createEvent(['name' => $prefix.' secret meeting', 'start_date' => '2033-03-10', 'unlisted' => 1]);

        $this->app['auth']->forgetGuards();

        $this->get('/2033/03/'.$prefix)->assertNotFound();
        $this->get('/2033/03/_')->assertNotFound();
        $this->get('/2033/03/%25')->assertNotFound();
    }

    public function testWildcardsAreMatchedLiterally()
    {
        $prefix = 'zq'.uniqid();
        $listed = $this->createEvent(['name' => $prefix.' public meetup', 'start_date' => '2033-04-10']);

        $this->app['auth']->forgetGuards();

        $this->get('/2033/04/'.$prefix)->assertRedirect($listed->permalink());
        $this->get('/2033/04/'.substr($prefix, 0, 2).'_'.substr($prefix, 3))->assertNotFound();
        $this->get('/2033/04/%25'.substr($prefix, 2))->assertNotFound();
    }
}
