<?php

namespace Tests\Feature;

use Tests\CreatesEvents;
use Tests\TestCase;

class ExportEventTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    public function testTheExportRequiresTheExactSecret()
    {
        $event = $this->createEvent();
        $this->app['auth']->forgetGuards();

        $this->get('/event/'.$event->id.'/export/'.$event->export_secret.'.json')
            ->assertOk()
            ->assertJson(['generator' => 'Meetable']);

        $this->get('/event/'.$event->id.'/export/wrong-secret.json')->assertForbidden();

        // A loose comparison treats these numeric strings as equal
        $event->export_secret = '1e3';
        $event->save();
        $this->get('/event/'.$event->id.'/export/1000.json')->assertForbidden();
    }
}
