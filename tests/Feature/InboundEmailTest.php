<?php

namespace Tests\Feature;

use App\Event;
use App\EventRevision;
use Illuminate\Support\Facades\DB;
use Tests\CreatesEvents;
use Tests\TestCase;

class InboundEmailTest extends TestCase
{
    use CreatesEvents;

    protected function tearDown(): void
    {
        $this->deleteTestData();

        parent::tearDown();
    }

    public function testAnInviteFromAKnownSenderCreatesAnEventAndARevision()
    {
        $name = 'Emailed Event '.uniqid();
        $this->forgetEvent($name);

        $response = $this->post('/email/sendgrid', ['email' => $this->invite($name, $this->senderAddress())]);

        $response->assertOk();
        $response->assertJson(['result' => 'success']);

        $event = Event::where('name', $name)->first();
        $this->assertNotNull($event, 'the event was not created');
        $this->assertNotEmpty($event->key);
        $this->assertNotEmpty($event->export_secret);

        // The revision is what used to fail: an event that has just been inserted
        // hasn't picked up the defaults the database filled in for it.
        $this->assertNotNull(EventRevision::where('event_id', $event->id)->first(), 'no revision was recorded');

        $this->actingAs($this->testUser())->get($event->permalink())->assertOk();
    }

    public function testAnInviteFromAnUnknownSenderIsRejected()
    {
        $name = 'Unknown Sender Event '.uniqid();
        $this->forgetEvent($name);

        $response = $this->post('/email/sendgrid', [
            'email' => $this->invite($name, 'nobody-'.uniqid().'@example.com'),
        ]);

        $response->assertOk();
        $response->assertJson(['result' => 'invalid_user']);
        $this->assertNull(Event::where('name', $name)->first());
    }

    /**
     * Registers an address the inbound handler will recognise as the test user.
     */
    private function senderAddress(): string
    {
        $address = 'inbound-'.uniqid().'@example.com';

        DB::table('user_emails')->insert([
            'user_id' => $this->testUser()->id,
            'email' => $address,
        ]);

        return $address;
    }

    /**
     * Builds the raw MIME message SendGrid would post to us.
     */
    private function invite(string $name, string $from): string
    {
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Meetable Tests//EN',
            'BEGIN:VEVENT',
            'UID:'.uniqid().'@example.com',
            'DTSTAMP:20320101T000000Z',
            'DTSTART:20320501T170000Z',
            'DTEND:20320501T180000Z',
            'SUMMARY:'.$name,
            'LOCATION:Somewhere',
            'END:VEVENT', 'END:VCALENDAR', '',
        ]);

        $boundary = 'boundary'.uniqid();

        return implode("\r\n", [
            'From: '.$from,
            'To: events@example.com',
            'Subject: Invitation',
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="'.$boundary.'"',
            '',
            '--'.$boundary,
            'Content-Type: text/plain',
            '',
            'You are invited.',
            '--'.$boundary,
            'Content-Type: text/calendar; name="invite.ics"',
            'Content-Disposition: attachment; filename="invite.ics"',
            '',
            $ics,
            '--'.$boundary.'--',
            '',
        ]);
    }
}
