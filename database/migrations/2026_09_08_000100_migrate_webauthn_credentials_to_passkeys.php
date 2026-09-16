<?php

use App\Helpers\CoseKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Passkey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\TrustPath\EmptyTrustPath;
use Laravel\Passkeys\Support\WebAuthn;

/**
 * Carries credentials registered with laragear/webauthn over to laravel/passkeys.
 *
 * The two packages store the same underlying credential in different shapes, so each
 * row is rebuilt into the CredentialRecord that web-auth/webauthn-lib expects. The
 * original webauthn_credentials table is left in place so this can be re-run.
 *
 * A credential that can't be converted is skipped rather than written half-formed:
 * the admin can register a new passkey with a link from `php artisan user:passkey-link`,
 * whereas a broken one would lock them out.
 */
return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('webauthn_credentials')) {
            return;
        }

        $credentials = DB::table('webauthn_credentials')->whereNull('disabled_at')->get();

        foreach($credentials as $credential) {
            try {
                $this->migrateCredential($credential);
            } catch(Throwable $e) {
                Log::warning('Could not migrate WebAuthn credential '.$credential->id.' to a passkey: '.$e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // The source rows are untouched by up(), so there is nothing to restore.
    }

    private function migrateCredential($credential): void
    {
        $model = Relation::getMorphedModel($credential->authenticatable_type) ?: $credential->authenticatable_type;

        if(ltrim($model, '\\') !== App\User::class
           || !DB::table('users')->where('id', $credential->authenticatable_id)->exists()) {
            return;
        }

        $id = Base64UrlSafe::decodeNoPadding($credential->id);
        $credential_id = Base64UrlSafe::encodeUnpadded($id);

        if(Passkey::where('credential_id', $credential_id)->exists()) {
            return;
        }

        $record = CredentialRecord::create(
            publicKeyCredentialId: $id,
            type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            transports: json_decode($credential->transports ?: '[]', true) ?: [],
            attestationType: 'none',
            trustPath: EmptyTrustPath::create(),
            aaguid: Uuid::fromString($this->uuid($credential->aaguid)),
            credentialPublicKey: CoseKey::from_pem(Crypt::decryptString($credential->public_key)),
            userHandle: $this->userHandle($credential->user_id),
            counter: (int)$credential->counter,
        );

        Passkey::forceCreate([
            'user_id' => $credential->authenticatable_id,
            'name' => $credential->alias ?: 'Passkey',
            'credential_id' => $credential_id,
            'credential' => json_decode(WebAuthn::toJson($record), true, flags: JSON_THROW_ON_ERROR),
            'created_at' => $credential->created_at,
            'updated_at' => $credential->updated_at,
        ]);
    }

    /**
     * Normalises a UUID that may have been stored with or without its dashes.
     */
    private function uuid(?string $uuid): string
    {
        $hex = str_replace('-', '', (string)$uuid);

        if(strlen($hex) !== 32) {
            $hex = str_repeat('0', 32);
        }

        return implode('-', [
            substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20),
        ]);
    }

    /**
     * Rebuilds the user handle the authenticator actually stored.
     *
     * laragear sent the credential's UUID as 32 hex characters, and its Javascript
     * helper base64url-decoded that string before handing it to the browser, so the
     * bytes on the device are the decoding of the hex text rather than the UUID itself.
     */
    private function userHandle(string $uuid): string
    {
        return Base64UrlSafe::decodeNoPadding(str_replace('-', '', $uuid));
    }
};
