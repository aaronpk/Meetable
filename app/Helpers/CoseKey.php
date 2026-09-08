<?php

namespace App\Helpers;

use CBOR\ByteStringObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\UnsignedIntegerObject;
use Cose\Algorithms;
use Cose\Key\Ec2Key;
use Cose\Key\Key;
use Cose\Key\OkpKey;
use Cose\Key\RsaKey;
use RuntimeException;

/**
 * Converts an X.509 SubjectPublicKeyInfo PEM into a CBOR-encoded COSE_Key.
 *
 * laragear/webauthn stored credential public keys as PEM, but web-auth/webauthn-lib
 * (used by laravel/passkeys) expects the COSE form that the authenticator originally
 * sent. This lets existing credentials be carried over instead of re-registered.
 */
class CoseKey {

    // OID 1.3.101.112, the DER-encoded AlgorithmIdentifier for Ed25519 keys. PHP's
    // openssl extension can't describe these, so the raw key is read out of the DER.
    const ED25519_SPKI_PREFIX = "\x30\x2a\x30\x05\x06\x03\x2b\x65\x70\x03\x21\x00";

    const EC_CURVES = [
        'prime256v1' => [Ec2Key::CURVE_P256, Algorithms::COSE_ALGORITHM_ES256, 32],
        'secp384r1'  => [Ec2Key::CURVE_P384, Algorithms::COSE_ALGORITHM_ES384, 48],
        'secp521r1'  => [Ec2Key::CURVE_P521, Algorithms::COSE_ALGORITHM_ES512, 66],
    ];

    public static function from_pem($pem) {
        if($cose = self::_ed25519_from_pem($pem)) {
            return $cose;
        }

        $key = openssl_pkey_get_public($pem);
        if(!$key) {
            throw new RuntimeException('Unable to read the public key: '.openssl_error_string());
        }

        $details = openssl_pkey_get_details($key);
        if(!$details) {
            throw new RuntimeException('Unable to read the public key details');
        }

        if(isset($details['ec'])) {
            return self::_ec2($details['ec']);
        }

        if(isset($details['rsa'])) {
            return self::_rsa($details['rsa']);
        }

        throw new RuntimeException('Unsupported public key type');
    }

    private static function _ec2($ec) {
        $curve = $ec['curve_name'] ?? null;

        if(!isset(self::EC_CURVES[$curve])) {
            throw new RuntimeException('Unsupported elliptic curve ['.$curve.']');
        }

        list($cose_curve, $alg, $length) = self::EC_CURVES[$curve];

        return self::_map([
            [Key::TYPE, Key::TYPE_EC2],
            [Key::ALG, $alg],
            [Ec2Key::DATA_CURVE, $cose_curve],
        ], [
            [Ec2Key::DATA_X, self::_pad($ec['x'], $length)],
            [Ec2Key::DATA_Y, self::_pad($ec['y'], $length)],
        ]);
    }

    private static function _rsa($rsa) {
        return self::_map([
            [Key::TYPE, Key::TYPE_RSA],
            [Key::ALG, Algorithms::COSE_ALGORITHM_RS256],
        ], [
            [RsaKey::DATA_N, $rsa['n']],
            [RsaKey::DATA_E, $rsa['e']],
        ]);
    }

    private static function _ed25519_from_pem($pem) {
        $der = self::_der($pem);

        if(strlen($der) != 44 || substr($der, 0, 12) !== self::ED25519_SPKI_PREFIX) {
            return null;
        }

        return self::_map([
            [Key::TYPE, Key::TYPE_OKP],
            [Key::ALG, Algorithms::COSE_ALGORITHM_EDDSA],
            [OkpKey::DATA_CURVE, OkpKey::CURVE_ED25519],
        ], [
            [OkpKey::DATA_X, substr($der, 12)],
        ]);
    }

    private static function _der($pem) {
        if(!preg_match('/-----BEGIN PUBLIC KEY-----(.+)-----END PUBLIC KEY-----/s', $pem, $match)) {
            return '';
        }

        return (string)base64_decode(preg_replace('/\s+/', '', $match[1]), true);
    }

    /**
     * Builds the CBOR map, taking the integer labels first and the byte string
     * labels second, since that's the order the COSE_Key parameters are defined in.
     */
    private static function _map($integers, $byte_strings) {
        $items = [];

        foreach($integers as list($label, $value)) {
            $items[] = MapItem::create(self::_int($label), self::_int($value));
        }

        foreach($byte_strings as list($label, $value)) {
            $items[] = MapItem::create(self::_int($label), ByteStringObject::create($value));
        }

        return MapObject::create($items)->__toString();
    }

    private static function _int($value) {
        return $value < 0
            ? NegativeIntegerObject::create($value)
            : UnsignedIntegerObject::create($value);
    }

    /**
     * openssl trims leading zero bytes from the coordinates, but COSE wants them
     * left-padded to the full field size.
     */
    private static function _pad($value, $length) {
        return str_pad($value, $length, "\x00", STR_PAD_LEFT);
    }

}
