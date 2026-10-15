<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

new Dotenv()->bootEnv(dirname(__DIR__).'/.env');

/*
 * The tests sign their own access tokens, as the authorization server would: a key pair made once, in var/.
 */
$keys = dirname(__DIR__).'/var/test-jwt';
if (!is_file($keys.'/private.pem')) {
    @mkdir($keys, 0o775, true);
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $private);
    file_put_contents($keys.'/private.pem', $private);
    file_put_contents($keys.'/public.pem', openssl_pkey_get_details($key)['key']);
}
