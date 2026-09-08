<?php

namespace Tests;

use App\Events\EventCreated;
use App\Events\EventUpdated;
use App\Events\ResizeImages;
use App\Events\UserCreated;
use App\Events\WebmentionReceived;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * The listeners for these all reach out over the network: three of them post to
     * or fetch a URL, and the last one downloads and resizes images. Left alone they
     * make the tests depend on the site's notification settings and on the machine
     * having somewhere to send them, so nothing is listening during a test run.
     *
     * A test that wants a listener back can dispatch the event itself.
     */
    const EVENTS_WITH_REMOTE_LISTENERS = [
        EventCreated::class,
        EventUpdated::class,
        UserCreated::class,
        WebmentionReceived::class,
        ResizeImages::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake(self::EVENTS_WITH_REMOTE_LISTENERS);
    }
}
