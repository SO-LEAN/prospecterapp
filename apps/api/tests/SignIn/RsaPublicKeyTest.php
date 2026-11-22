<?php

declare(strict_types=1);

namespace Tests\App\SignIn;

use App\Shared\Typed;
use App\SignIn\RsaPublicKey;
use PHPUnit\Framework\TestCase;

class RsaPublicKeyTest extends TestCase
{
    public function testTheModulusAndExponentOfAKeyGiveItsPem(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, $key);
        $details = openssl_pkey_get_details($key);
        $this->assertIsArray($details);
        $rsa = Typed::document($details['rsa']);

        $this->assertSame($details['key'], RsaPublicKey::pemOf(self::base64Url(Typed::string($rsa['n'])), self::base64Url(Typed::string($rsa['e']))));
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
