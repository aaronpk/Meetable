<?php

namespace Tests\Feature;

use App\Event;
use App\Setting;
use Tests\CreatesEvents;
use Tests\TestCase;

class ZoomWebhookTest extends TestCase
{
    use CreatesEvents;

    const SECRET = 'test-webhook-secret';

    private $previous_secret;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::$cached = [];
        $this->previous_secret = Setting::value('zoom_webhook_secret');
        $this->setSecret(self::SECRET);
    }

    protected function tearDown(): void
    {
        $this->setSecret($this->previous_secret);
        $this->deleteTestData();

        parent::tearDown();
    }

    private function setSecret($secret)
    {
        Setting::set('zoom_webhook_secret', $secret);
        Setting::$cached = [];
    }

    private function send(array $body, $timestamp = null, $secret = self::SECRET)
    {
        $json = json_encode($body);
        $timestamp = $timestamp ?? time();
        $signature = 'v0='.hash_hmac('sha256', 'v0:'.$timestamp.':'.$json, $secret);

        return $this->call('POST', '/api/zoom/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ZM_REQUEST_TIMESTAMP' => (string)$timestamp,
            'HTTP_X_ZM_SIGNATURE' => $signature,
        ], $json);
    }

    private function meetingEvent()
    {
        $event = $this->createEvent();
        $event->zoom_meeting_id = (string)random_int(10000000000, 99999999999);
        $event->save();
        return $event;
    }

    public function testUrlValidationSignsZoomsToken()
    {
        $this->postJson('/api/zoom/webhook', [
            'event' => 'endpoint.url_validation',
            'payload' => ['plainToken' => 'qgg8vlvZRS6UYooatFL8Aw'],
        ])->assertOk()->assertJson([
            'plainToken' => 'qgg8vlvZRS6UYooatFL8Aw',
            'encryptedToken' => hash_hmac('sha256', 'qgg8vlvZRS6UYooatFL8Aw', self::SECRET),
        ]);
    }

    public function testUrlValidationCannotBeUsedToSignAWebhook()
    {
        $body = json_encode(['event' => 'meeting.ended', 'payload' => ['object' => ['id' => '123']]]);

        $this->postJson('/api/zoom/webhook', [
            'event' => 'endpoint.url_validation',
            'payload' => ['plainToken' => 'v0:'.time().':'.$body],
        ])->assertStatus(400)->assertJsonMissing(['encryptedToken']);
    }

    public function testASignedWebhookUpdatesTheEvent()
    {
        $event = $this->meetingEvent();

        $this->send(['event' => 'meeting.started', 'payload' => ['object' => ['id' => $event->zoom_meeting_id]]])
            ->assertOk();

        $this->assertEquals('started', $event->fresh()->zoom_meeting_status);
    }

    public function testBadSignaturesAndOldTimestampsAreRejected()
    {
        $event = $this->meetingEvent();
        $body = ['event' => 'meeting.started', 'payload' => ['object' => ['id' => $event->zoom_meeting_id]]];

        $this->send($body, null, 'wrong-secret')->assertUnauthorized();
        $this->send($body, time() - 3600)->assertUnauthorized();

        $this->assertNotEquals('started', $event->fresh()->zoom_meeting_status);
    }

    public function testAWebhookWithoutAMeetingIdChangesNothing()
    {
        // Events without a Zoom meeting have an empty zoom_meeting_id, so an empty id must not match them
        $this->createEvent();
        $started = function() {
            return Event::where('zoom_meeting_id', '')->where('zoom_meeting_status', 'started')->count();
        };
        $before = $started();

        $this->send(['event' => 'meeting.started', 'payload' => ['object' => ['id' => '']]])->assertOk();
        $this->send(['event' => 'meeting.started', 'payload' => ['object' => []]])->assertOk();

        $this->assertEquals($before, $started());
    }

    public function testNothingIsAcceptedWithoutASecret()
    {
        $this->setSecret('');

        $this->postJson('/api/zoom/webhook', [
            'event' => 'endpoint.url_validation',
            'payload' => ['plainToken' => 'qgg8vlvZRS6UYooatFL8Aw'],
        ])->assertNotFound();

        $this->send(['event' => 'meeting.started', 'payload' => ['object' => ['id' => '1']]], null, '')->assertNotFound();
    }
}
