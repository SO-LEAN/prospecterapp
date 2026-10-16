<?php

namespace App\SignIn;

/**
 * An RSA public key of a JWKS (its modulus and exponent, base64url) as the PEM a signature is checked with:
 * the DER of a SubjectPublicKeyInfo, which OpenSSL cannot build from the two numbers alone.
 */
final class RsaPublicKey
{
    private const string RSA_ENCRYPTION = "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
    private const string INTEGER = "\x02";
    private const string BIT_STRING = "\x03";
    private const string SEQUENCE = "\x30";

    public static function pemOf(string $modulus, string $exponent): string
    {
        $key = self::der(self::SEQUENCE, self::integer(self::decoded($modulus)).self::integer(self::decoded($exponent)));
        $info = self::der(self::SEQUENCE, self::der(self::SEQUENCE, self::RSA_ENCRYPTION).self::der(self::BIT_STRING, "\x00".$key));

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($info), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    private static function integer(string $bytes): string
    {
        $positive = ord($bytes[0]) >= 0x80 ? "\x00".$bytes : $bytes;

        return self::der(self::INTEGER, $positive);
    }

    private static function der(string $type, string $content): string
    {
        $length = strlen($content);

        if ($length < 0x80) {
            return $type.chr($length).$content;
        }

        $lengthBytes = ltrim(pack('N', $length), "\x00");

        return $type.chr(0x80 | strlen($lengthBytes)).$lengthBytes.$content;
    }

    private static function decoded(string $base64Url): string
    {
        return (string) base64_decode(strtr($base64Url, '-_', '+/'), true);
    }
}
