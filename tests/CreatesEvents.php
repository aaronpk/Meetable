<?php

namespace Tests;

use App\Event;
use App\EventRevision;
use App\User;
use Illuminate\Support\Facades\DB;

/**
 * Gives a test its own user and a way to create events that are cleaned up afterwards.
 */
trait CreatesEvents
{
    protected $test_user;
    protected $test_event_names = [];

    protected function testUser(): User
    {
        if(!$this->test_user) {
            $this->test_user = new User;
            $this->test_user->identifier = 'test-'.uniqid();
            $this->test_user->email = $this->test_user->identifier.'@example.com';
            $this->test_user->name = 'Test User';
            $this->test_user->is_admin = false;
            $this->test_user->save();
        }

        return $this->test_user;
    }

    /**
     * Creates an event through the same controller the web form posts to.
     */
    protected function createEvent(array $fields = []): Event
    {
        $name = $fields['name'] ?? 'Test Event '.uniqid();
        $this->test_event_names[] = $name;

        $this->actingAs($this->testUser())
            ->post('/create', array_merge([
                'name' => $name,
                'start_date' => '2032-01-01',
                'status' => 'confirmed',
            ], $fields, ['name' => $name]))
            ->assertRedirect();

        return Event::where('name', $name)->orderBy('id', 'desc')->firstOrFail();
    }

    protected function forgetEvent(string $name): void
    {
        $this->test_event_names[] = $name;
    }

    protected function deleteTestData(): void
    {
        if($this->test_event_names) {
            $ids = Event::withTrashed()->whereIn('name', $this->test_event_names)->pluck('id');

            EventRevision::whereIn('event_id', $ids)->forceDelete();
            DB::table('event_tag')->whereIn('event_id', $ids)->delete();
            Event::whereIn('id', $ids)->forceDelete();
        }

        if($this->test_user) {
            DB::table('user_emails')->where('user_id', $this->test_user->id)->delete();
            User::where('id', $this->test_user->id)->delete();
        }
    }
}
