<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Auth;
use App\User;
use App\Jobs\DownloadProfilePhoto;
use App\Jobs\FetchProfileInfo;
use App\Http\Controllers\Auth\CompletesOAuthLogin;
use App\Services\Auth\VouchGuard;

/**
 * Profile details are refreshed from the login provider on every login, not just the first.
 */
class ProfileRefreshTest extends TestCase
{
    private $identifiers = [];

    protected function tearDown(): void
    {
        User::whereIn('identifier', $this->identifiers)->delete();
        parent::tearDown();
    }

    private function identifier($prefix)
    {
        $identifier = $prefix.'-'.uniqid();
        $this->identifiers[] = $identifier;
        return $identifier;
    }

    private function syncer()
    {
        return new class {
            use CompletesOAuthLogin;
            public function sync($identifier, $fields, $photo_url = null, $defaults = []) {
                return $this->syncUser($identifier, $fields, $photo_url, $defaults);
            }
        };
    }

    public function testAProviderLoginCreatesTheUserAndQueuesThePhoto()
    {
        Queue::fake();
        $identifier = $this->identifier('https://provider.example/alice');

        $user = $this->syncer()->sync($identifier, ['name' => 'Alice', 'email' => 'alice@example.com'], 'https://provider.example/alice.jpg', ['url' => '']);

        $this->assertNotNull($user->id);
        $this->assertEquals($identifier, $user->identifier);
        $this->assertEquals('Alice', $user->name);
        $this->assertEquals('alice@example.com', $user->email);
        $this->assertEquals('', $user->url);
        $this->assertEquals(80, strlen($user->api_token));
        $this->assertNull($user->photo);

        Queue::assertPushed(DownloadProfilePhoto::class, function($job) use($user) {
            return $job->user->id == $user->id && $job->url == 'https://provider.example/alice.jpg';
        });
    }

    public function testLoggingInAgainUpdatesTheProfileAndQueuesThePhotoAgain()
    {
        Queue::fake();
        $identifier = $this->identifier('https://provider.example/bob');

        $first = $this->syncer()->sync($identifier, ['name' => 'Bob', 'email' => 'bob@example.com'], 'https://provider.example/bob.jpg', ['url' => 'https://bob.example/']);
        $first->photo = '/storage/users/old.jpg';
        $first->save();

        $again = $this->syncer()->sync($identifier, ['name' => 'Robert', 'email' => 'robert@example.com'], 'https://provider.example/bob2.jpg', ['url' => 'https://ignored.example/']);

        $this->assertEquals($first->id, $again->id);
        $this->assertEquals('Robert', $again->name);
        $this->assertEquals('robert@example.com', $again->email);
        $this->assertEquals('https://bob.example/', $again->url, 'defaults only apply to new users');
        $this->assertEquals($first->api_token, $again->api_token);
        $this->assertEquals('/storage/users/old.jpg', $again->photo, 'the photo is replaced by the job, not the login');

        Queue::assertPushed(DownloadProfilePhoto::class, 2);
        Queue::assertPushed(DownloadProfilePhoto::class, function($job) {
            return $job->url == 'https://provider.example/bob2.jpg';
        });
    }

    public function testNoPhotoFromTheProviderQueuesNothing()
    {
        Queue::fake();
        $identifier = $this->identifier('https://provider.example/carol');

        $this->syncer()->sync($identifier, ['name' => 'Carol']);

        Queue::assertNothingPushed();
    }

    public function testAPhotoThatCannotBeDownloadedLeavesTheCurrentPhotoAlone()
    {
        $user = new User;
        $user->identifier = $this->identifier('https://provider.example/dave');
        $user->name = 'Dave';
        $user->photo = '/storage/users/keep.jpg';
        $user->save();

        // SafeHTTP refuses private addresses, so this download fails
        (new DownloadProfilePhoto($user, 'http://127.0.0.1/avatar.jpg'))->handle();
        $this->assertEquals('/storage/users/keep.jpg', $user->fresh()->photo);

        (new DownloadProfilePhoto($user, ''))->handle();
        $this->assertEquals('/storage/users/keep.jpg', $user->fresh()->photo);
    }

    public function testAnUnreachableHCardLeavesTheProfileAlone()
    {
        $user = new User;
        $user->identifier = $this->identifier('http://127.0.0.1/erin');
        $user->url = 'http://127.0.0.1/erin';
        $user->name = 'Erin';
        $user->photo = '/storage/users/erin.jpg';
        $user->save();

        $user->fetchProfileInfo();

        $this->assertEquals('Erin', $user->fresh()->name);
        $this->assertEquals('/storage/users/erin.jpg', $user->fresh()->photo);
    }

    private function vouchRequest($identifier, $session = null)
    {
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_USER' => $identifier]);
        if($session)
            $request->setLaravelSession($session);
        return $request;
    }

    public function testVouchRefreshesTheHCardOncePerSession()
    {
        Queue::fake();
        $provider = Auth::createUserProvider('users');
        $identifier = $this->identifier('https://vouch.example/frank');

        $user = new User;
        $user->identifier = $identifier;
        $user->url = $identifier;
        $user->save();

        $session = app('session.store');
        $session->setId(null);
        $session->start();

        $guard = new VouchGuard($provider, $this->vouchRequest($identifier, $session));
        $this->assertEquals($user->id, $guard->user()->id);
        $guard->user();
        Queue::assertPushed(FetchProfileInfo::class, 1);

        // A later request in the same session doesn't fetch again
        $guard = new VouchGuard($provider, $this->vouchRequest($identifier, $session));
        $guard->user();
        Queue::assertPushed(FetchProfileInfo::class, 1);
        $this->assertTrue($session->get('VOUCH_PROFILE_REFRESHED'));
    }

    public function testVouchDoesNotRefreshWithoutASessionOrForANewUser()
    {
        Queue::fake();
        $provider = Auth::createUserProvider('users');

        $identifier = $this->identifier('https://vouch.example/grace');
        $user = new User;
        $user->identifier = $identifier;
        $user->url = $identifier;
        $user->save();

        // No session, such as an API request
        $guard = new VouchGuard($provider, $this->vouchRequest($identifier));
        $this->assertEquals($user->id, $guard->user()->id);
        Queue::assertNothingPushed();

        // A brand new user is handled by the UserCreated event instead
        $session = app('session.store');
        $session->setId(null);
        $session->start();
        $session->flush();
        $new = $this->identifier('https://vouch.example/heidi');
        $guard = new VouchGuard($provider, $this->vouchRequest($new, $session));
        $this->assertEquals($new, $guard->user()->identifier);
        Queue::assertNothingPushed();
    }
}
