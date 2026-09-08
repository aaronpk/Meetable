<?php

namespace Tests\Unit;

use App\Helpers\CoseKey;
use CBOR\Decoder;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\EdDSA\Ed25519;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Key\Key;
use RuntimeException;
use Tests\TestCase;
use Webauthn\StringStream;
use Webauthn\U2FPublicKey;
use Webauthn\Util\CoseSignatureFixer;

class CoseKeyTest extends TestCase
{
    public function testEllipticCurveKeys()
    {
        foreach([
            ['prime256v1', ES256::create(), OPENSSL_ALGO_SHA256, -7],
            ['secp384r1', ES384::create(), OPENSSL_ALGO_SHA384, -35],
        ] as list($curve, $algorithm, $digest, $expected_alg)) {
            $private = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => $curve]);
            $key = $this->parse(openssl_pkey_get_details($private)['key']);

            $this->assertEquals($expected_alg, $key->alg(), $curve);

            $data = random_bytes(64);
            openssl_sign($data, $signature, $private, $digest);

            $this->assertTrue($algorithm->verify($data, $key, CoseSignatureFixer::fix($signature, $algorithm)), $curve);
            $this->assertFalse($algorithm->verify(random_bytes(64), $key, CoseSignatureFixer::fix($signature, $algorithm)), $curve);
        }
    }

    public function testRsaKeys()
    {
        $private = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $key = $this->parse(openssl_pkey_get_details($private)['key']);

        $this->assertEquals(-257, $key->alg());

        $data = random_bytes(64);
        openssl_sign($data, $signature, $private, OPENSSL_ALGO_SHA256);

        $this->assertTrue(RS256::create()->verify($data, $key, $signature));
        $this->assertFalse(RS256::create()->verify(random_bytes(64), $key, $signature));
    }

    public function testEd25519Keys()
    {
        if(!function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('ext-sodium is not available');
        }

        $pair = sodium_crypto_sign_keypair();
        $public = sodium_crypto_sign_publickey($pair);

        $der = CoseKey::ED25519_SPKI_PREFIX.$public;
        $key = $this->parse("-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n");

        $this->assertEquals(-8, $key->alg());

        $data = random_bytes(64);
        $signature = sodium_crypto_sign_detached($data, sodium_crypto_sign_secretkey($pair));

        $this->assertTrue(Ed25519::create()->verify($data, $key, $signature));
        $this->assertFalse(Ed25519::create()->verify(random_bytes(64), $key, $signature));
    }

    /**
     * The P-256 encoding should be byte for byte what webauthn-lib produces itself.
     */
    public function testMatchesTheEncodingUsedByWebauthnLib()
    {
        $private = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $ec = openssl_pkey_get_details($private)['ec'];

        $point = "\x04".str_pad($ec['x'], 32, "\x00", STR_PAD_LEFT).str_pad($ec['y'], 32, "\x00", STR_PAD_LEFT);

        $this->assertEquals(
            U2FPublicKey::convertToCoseKey($point),
            CoseKey::from_pem(openssl_pkey_get_details($private)['key'])
        );
    }

    public function testRejectsSomethingThatIsNotAKey()
    {
        $this->expectException(RuntimeException::class);

        CoseKey::from_pem('-----BEGIN PUBLIC KEY-----\nnope\n-----END PUBLIC KEY-----');
    }

    /**
     * Converts the PEM and reads it back the way the assertion check does.
     */
    private function parse($pem): Key
    {
        $stream = new StringStream(CoseKey::from_pem($pem));
        $decoded = Decoder::create()->decode($stream);

        $this->assertTrue($stream->isEOF(), 'the COSE key had trailing bytes');

        return Key::create($decoded->normalize());
    }
}
