<?php

namespace Tests\Feature;

use App\Helpers\Uri;
use App\Http\Controllers\Auth\CompletesOAuthLogin;
use App\Services\Auth\VouchGuard;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginFlowTest extends TestCase
{
    const CALLBACKS = [
        '/auth/github' => 'GITHUB_OAUTH_STATE',
        '/auth/heroku' => 'HEROKU_OAUTH_STATE',
        '/auth/discord' => 'DISCORD_OAUTH_STATE',
        '/auth/oidc' => 'OIDC_STATE',
    ];

    public function testACallbackWithoutAStartedLoginIsRejected()
    {
        foreach(self::CALLBACKS as $url => $key) {
            // No state in the session, as when an attacker sends someone their own callback link
            $this->get($url.'?code=attacker-code')
                ->assertOk()
                ->assertSee('Invalid OAuth State');

            $this->get($url.'?code=attacker-code&state=')
                ->assertSee('Invalid OAuth State');

            $this->withSession([$key => 'expected-state'])
                ->get($url.'?code=attacker-code&state=other-state')
                ->assertSee('Invalid OAuth State');

            $this->flushSession();
        }
    }

    public function testTheStateCanOnlyBeUsedOnce()
    {
        $this->withSession(['GITHUB_OAUTH_STATE' => 'expected-state'])
            ->get('/auth/github?state=expected-state')
            ->assertSee('did not complete successfully');

        $this->assertNull(session('GITHUB_OAUTH_STATE'));

        $this->get('/auth/github?state=expected-state')
            ->assertSee('Invalid OAuth State');
    }

    public function testOnlyPagesOnThisSiteAreUsedAfterLoggingIn()
    {
        $this->assertEquals('/event/123', Uri::same_origin_path('/event/123'));
        $this->assertEquals('http://localhost/tags?x=1', Uri::same_origin_path('http://localhost/tags?x=1'));

        foreach(['https://evil.example/', '//evil.example/', '/\\evil.example', "/\t/evil.example", 'javascript:alert(1)', 'http://user@evil.example/', 'relative', ''] as $url) {
            $this->assertEquals('/', Uri::same_origin_path($url), json_encode($url));
        }
    }

    public function testLoggingInStartsANewSessionAndIgnoresForeignReturnUrls()
    {
        $this->startSession();
        session(['AUTH_RETURN_TO' => 'https://evil.example/phish']);
        $old_id = session()->getId();

        $controller = new class {
            use CompletesOAuthLogin;
            public function finish() {
                return $this->redirectAfterLogin('TEST_USER', 'user-123');
            }
        };

        $response = $controller->finish();

        $this->assertEquals(url('/'), $response->getTargetUrl());
        $this->assertNotEquals($old_id, session()->getId());
        $this->assertEquals('user-123', session('TEST_USER'));
        $this->assertNull(session('AUTH_RETURN_TO'));
    }

    public function testVouchIgnoresARemoteUserHeader()
    {
        $provider = Auth::createUserProvider('users');

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_REMOTE_USER' => 'https://admin.example/']);
        $guard = new VouchGuard($provider, $request);
        $this->assertFalse($guard->check());
        $this->assertNull($guard->user());

        $identifier = 'https://vouch-'.uniqid().'.example/';
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_USER' => $identifier]);
        $guard = new VouchGuard($provider, $request);
        $this->assertTrue($guard->check());
        $this->assertEquals($identifier, $guard->user()->identifier);

        User::where('identifier', $identifier)->delete();
    }

    public function testWebmentionsAreOnlyApprovedFromWithinTheUsersUrl()
    {
        $this->assertTrue(Uri::url_is_under('https://aaronparecki.com/2026/09/16/1/', 'https://aaronparecki.com/'));
        $this->assertTrue(Uri::url_is_under('https://aaronparecki.com/2026/', 'https://aaronparecki.com'));
        $this->assertTrue(Uri::url_is_under('https://github.com/aaronpk/Meetable/issues/1', 'https://github.com/aaronpk'));
        $this->assertTrue(Uri::url_is_under('http://github.com/aaronpk', 'https://github.com/aaronpk/'));

        $this->assertFalse(Uri::url_is_under('https://github.com/someone-else/repo', 'https://github.com/aaronpk'));
        $this->assertFalse(Uri::url_is_under('https://github.com/aaronpkevil', 'https://github.com/aaronpk'));
        $this->assertFalse(Uri::url_is_under('https://aaronparecki.com.evil.example/', 'https://aaronparecki.com/'));
    }
}
