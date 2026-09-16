<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passkeys\Passkey;
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

    private $admin;

    protected function tearDown(): void
    {
        if($this->admin) {
            Passkey::where('user_id', $this->admin->id)->delete();
            $this->admin->delete();
        }
        User::where('email', 'like', 'new-admin-%@example.com')->delete();

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

    private function adminWithoutPasskey(): User
    {
        $this->admin = new User;
        $this->admin->identifier = 'admin-'.uniqid().'@example.com';
        $this->admin->email = $this->admin->identifier;
        $this->admin->name = 'Admin';
        $this->admin->is_admin = true;
        $this->admin->save();
        return $this->admin;
    }

    public function testTheLoginPageDoesNotSignInAnAdminWithoutAPasskey()
    {
        $this->adminWithoutPasskey();

        $this->get('/login')
            ->assertOk()
            ->assertSee('user:passkey-link');

        $this->assertGuest();
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function testCreatingAnAdminIsRefusedOnceAnAdminExists()
    {
        $admin = $this->adminWithoutPasskey();

        $this->post('/auth/create-user', ['name' => 'Attacker', 'email' => 'new-admin-'.uniqid().'@example.com'])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertEquals(0, User::where('email', 'like', 'new-admin-%@example.com')->count());
    }

    public function testTheFirstAdminCanBeCreatedAndRegistersAPasskey()
    {
        if(User::where('is_admin', 1)->exists())
            $this->markTestSkipped('The test database already has an admin user');

        $email = 'new-admin-'.uniqid().'@example.com';

        $this->get('/login')->assertOk()->assertSee('Create Admin User');

        $this->post('/auth/create-user', ['name' => 'First Admin', 'email' => $email])
            ->assertRedirect('/login');

        $this->assertAuthenticatedAs(User::where('email', $email)->firstOrFail());
        $this->get('/login')->assertOk()->assertSee('Register a Passkey');
    }

    private function passkeyLink(User $user): string
    {
        $this->assertEquals(0, Artisan::call('user:passkey-link', ['email' => $user->email]));
        preg_match('~https?://\S+~', Artisan::output(), $match);
        return $match[0];
    }

    public function testAPasskeyLinkSignsInOnce()
    {
        $admin = $this->adminWithoutPasskey();
        $url = $this->passkeyLink($admin);

        $this->get($url)->assertOk()->assertSee('Register a Passkey');
        $this->assertAuthenticatedAs($admin);

        auth()->logout();
        $this->flushSession();

        $this->get($url)->assertForbidden();
        $this->assertGuest();
    }

    public function testAPasskeyLinkExpiresAndCannotBeTamperedWith()
    {
        $admin = $this->adminWithoutPasskey();
        $url = $this->passkeyLink($admin);

        $this->get(preg_replace('~/passkey-link/\d+~', '/passkey-link/'.$this->testUser()->id, $url))->assertForbidden();

        $this->travel(31)->minutes();
        $this->get($url)->assertForbidden();
        $this->assertGuest();
    }

    public function testASignedInUserReachesThoseRoutes()
    {
        foreach(self::PROTECTED_URLS as $url) {
            $this->actingAs($this->testUser())->get($url)->assertOk();
        }
    }
}
