<?php

namespace Tests;

use CBOR\ByteStringObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\TextStringObject;
use ParagonIE\ConstantTime\Base64UrlSafe;

/**
 * A software WebAuthn authenticator, enough to drive the passkey ceremonies in tests.
 *
 * It holds an EC P-256 key pair and produces the same attestation and assertion
 * payloads a real device would, so the registration and login endpoints can be
 * exercised end to end without a browser.
 */
class VirtualAuthenticator
{
    const FLAG_USER_PRESENT = 0x01;
    const FLAG_USER_VERIFIED = 0x04;
    const FLAG_ATTESTED_DATA = 0x40;

    public $key;
    public $credential_id;
    public $user_handle;
    public $aaguid;
    public $counter = 0;

    public function __construct(string $user_handle, ?string $aaguid = null)
    {
        $this->key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);

        $this->credential_id = random_bytes(32);
        $this->user_handle = $user_handle;
        $this->aaguid = $aaguid ?: random_bytes(16);
    }

    /**
     * Returns the public key as the X.509 PEM that laragear/webauthn used to store.
     */
    public function publicKeyPem(): string
    {
        return openssl_pkey_get_details($this->key)['key'];
    }

    /**
     * Returns the public key in the COSE form a real authenticator reports.
     */
    public function coseKey(): string
    {
        $ec = openssl_pkey_get_details($this->key)['ec'];

        $point = "\x04".str_pad($ec['x'], 32, "\x00", STR_PAD_LEFT).str_pad($ec['y'], 32, "\x00", STR_PAD_LEFT);

        return \Webauthn\U2FPublicKey::convertToCoseKey($point);
    }

    /**
     * Builds the credential a browser would hand back from navigator.credentials.create().
     */
    public function attest(string $challenge, string $origin, string $rp_id): array
    {
        $client_data = $this->clientData('webauthn.create', $challenge, $origin);

        $auth_data = $this->authenticatorData($rp_id, self::FLAG_ATTESTED_DATA)
            .$this->aaguid
            .pack('n', strlen($this->credential_id))
            .$this->credential_id
            .$this->coseKey();

        $attestation_object = MapObject::create([
            MapItem::create(TextStringObject::create('fmt'), TextStringObject::create('none')),
            MapItem::create(TextStringObject::create('attStmt'), MapObject::create([])),
            MapItem::create(TextStringObject::create('authData'), ByteStringObject::create($auth_data)),
        ])->__toString();

        return $this->credential([
            'clientDataJSON' => $client_data,
            'attestationObject' => $attestation_object,
        ]);
    }

    /**
     * Builds the credential a browser would hand back from navigator.credentials.get().
     */
    public function assert(string $challenge, string $origin, string $rp_id): array
    {
        $this->counter++;

        $client_data = $this->clientData('webauthn.get', $challenge, $origin);
        $auth_data = $this->authenticatorData($rp_id);

        openssl_sign($auth_data.hash('sha256', $client_data, true), $signature, $this->key, OPENSSL_ALGO_SHA256);

        return $this->credential([
            'clientDataJSON' => $client_data,
            'authenticatorData' => $auth_data,
            'signature' => $signature,
            'userHandle' => $this->user_handle,
        ]);
    }

    private function credential(array $response): array
    {
        return [
            'id' => Base64UrlSafe::encodeUnpadded($this->credential_id),
            'rawId' => Base64UrlSafe::encodeUnpadded($this->credential_id),
            'type' => 'public-key',
            'response' => array_map([Base64UrlSafe::class, 'encodeUnpadded'], $response),
        ];
    }

    private function clientData(string $type, string $challenge, string $origin): string
    {
        return json_encode([
            'type' => $type,
            'challenge' => $challenge,
            'origin' => $origin,
            'crossOrigin' => false,
        ]);
    }

    private function authenticatorData(string $rp_id, int $extra_flags = 0): string
    {
        return hash('sha256', $rp_id, true)
            .chr(self::FLAG_USER_PRESENT | self::FLAG_USER_VERIFIED | $extra_flags)
            .pack('N', $this->counter);
    }
}
