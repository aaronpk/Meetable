<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Laravel\Passkeys\Passkey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Tests\TestCase;
use Tests\VirtualAuthenticator;

class PasskeyTest extends TestCase
{
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User;
        $this->user->identifier = 'passkey-test-'.uniqid();
        $this->user->email = $this->user->identifier.'@example.com';
        $this->user->name = 'Passkey Test';
        $this->user->save();
    }

    protected function tearDown(): void
    {
        if($this->user) {
            Passkey::where('user_id', $this->user->id)->delete();
            DB::table('webauthn_credentials')->where('authenticatable_id', $this->user->id)->delete();
            User::where('id', $this->user->id)->delete();
        }

        parent::tearDown();
    }

    public function testRegisterAndLoginWithAPasskey()
    {
        $authenticator = $this->register();

        $this->assertDatabaseHas('passkeys', [
            'user_id' => $this->user->id,
            'name' => 'Test Device',
            'credential_id' => Base64UrlSafe::encodeUnpadded($authenticator->credential_id),
        ]);

        $this->login($authenticator);
    }

    public function testCredentialsFromLaragearWebauthnStillWork()
    {
        $uuid = bin2hex(random_bytes(16));

        $authenticator = new VirtualAuthenticator(
            // laragear sent the user handle as 32 hex characters that its Javascript
            // base64url-decoded, so the bytes the device stored are the decoded text.
            Base64UrlSafe::decodeNoPadding($uuid)
        );

        $this->seedLaragearCredential($authenticator, $uuid);

        $this->runCredentialMigration();

        $passkey = Passkey::where('user_id', $this->user->id)->first();
        $this->assertNotNull($passkey, 'the credential was not carried over');
        $this->assertEquals('Old Laptop', $passkey->name);
        $this->assertEquals(Base64UrlSafe::encodeUnpadded($authenticator->credential_id), $passkey->credential_id);

        $this->login($authenticator);
    }

    public function testBrokenCredentialsAreSkippedRatherThanImportedHalfFormed()
    {
        $authenticator = new VirtualAuthenticator(random_bytes(24));

        $this->seedLaragearCredential($authenticator, bin2hex(random_bytes(16)), 'not a public key');

        $this->runCredentialMigration();

        $this->assertNull(Passkey::where('user_id', $this->user->id)->first());
    }

    /**
     * Re-runs the migration that carries laragear credentials over to passkeys.
     */
    private function runCredentialMigration(): void
    {
        $migration = require database_path('migrations/2026_09_08_000100_migrate_webauthn_credentials_to_passkeys.php');

        $migration->up();
    }

    /**
     * Runs the registration ceremony and returns the authenticator that holds the key.
     */
    private function register(): VirtualAuthenticator
    {
        $authenticator = new VirtualAuthenticator(random_bytes(24));

        $options = $this->actingAs($this->user)->getJson('/user/passkeys/options')
            ->assertOk()
            ->json('options');

        // A real device keeps the user handle the server hands it at registration
        $authenticator->user_handle = Base64UrlSafe::decodeNoPadding($options['user']['id']);

        $this->actingAs($this->user)->postJson('/user/passkeys', [
            'name' => 'Test Device',
            'credential' => $authenticator->attest($options['challenge'], $this->origin(), $options['rp']['id']),
        ])->assertOk()->assertJson(['status' => 'passkey-registered']);

        return $authenticator;
    }

    /**
     * Runs the login ceremony and asserts the user ends up authenticated.
     */
    private function login(VirtualAuthenticator $authenticator): void
    {
        auth()->logout();
        session()->flush();

        $options = $this->getJson('/passkeys/login/options')->assertOk()->json('options');

        $this->postJson('/passkeys/login', [
            'credential' => $authenticator->assert($options['challenge'], $this->origin(), $options['rpId']),
        ])->assertOk();

        $this->assertAuthenticatedAs($this->user->fresh());
    }

    /**
     * Writes the row laragear/webauthn would have stored for this credential.
     */
    private function seedLaragearCredential(VirtualAuthenticator $authenticator, string $uuid, ?string $pem = null): void
    {
        DB::table('webauthn_credentials')->insert([
            'id' => Base64UrlSafe::encodeUnpadded($authenticator->credential_id),
            'authenticatable_type' => User::class,
            'authenticatable_id' => $this->user->id,
            'user_id' => $uuid,
            'alias' => 'Old Laptop',
            'counter' => 0,
            'rp_id' => parse_url(config('app.url'), PHP_URL_HOST),
            'origin' => $this->origin(),
            'transports' => json_encode(['internal']),
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'public_key' => Crypt::encryptString($pem ?: $authenticator->publicKeyPem()),
            'attestation_format' => 'none',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function origin(): string
    {
        return rtrim(config('app.url'), '/');
    }
}
