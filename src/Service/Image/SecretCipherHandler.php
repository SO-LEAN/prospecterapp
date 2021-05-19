<?php

namespace App\Service\Image;

use App\Traits\Base64Urlize;

class SecretCipherHandler implements CipherHandler
{
    use Base64Urlize;

    const CYPHER_METHOD = 'AES-128-CBC';

    /**
     * @var string
     */
    private $secret;
    /**
     * @var string
     */
    private $vi;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
        //const vi will produce deterministic cypher text
        $this->vi = str_pad('1', openssl_cipher_iv_length(self::CYPHER_METHOD));
    }

    /**
     * {@inheritdoc}
     */
    public function decode($input)
    {
        return openssl_decrypt($this->unurlize($input), self::CYPHER_METHOD, $this->secret, 0, $this->vi);
    }

    /**
     * {@inheritdoc}
     */
    public function encode($input)
    {
        return $this->urlize(base64_encode(openssl_encrypt($input, self::CYPHER_METHOD, $this->secret, OPENSSL_RAW_DATA, $this->vi)));
    }
}
