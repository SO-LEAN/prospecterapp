<?php

namespace App\Service\Image;

class SecretCipherHandler implements CipherHandler
{
    use Base64Urlize;

    public const CYPHER_METHOD = 'AES-128-CBC';
    private string $vi;

    public function __construct(private string $secret)
    {
        // const vi will produce deterministic cypher text
        $this->vi = str_pad('1', openssl_cipher_iv_length(self::CYPHER_METHOD));
    }

    public function decode(string $input): string|false
    {
        return openssl_decrypt($this->unurlize($input), self::CYPHER_METHOD, $this->secret, 0, $this->vi);
    }

    public function encode(string $input): string
    {
        return $this->urlize(base64_encode(openssl_encrypt($input, self::CYPHER_METHOD, $this->secret, OPENSSL_RAW_DATA, $this->vi)));
    }
}
