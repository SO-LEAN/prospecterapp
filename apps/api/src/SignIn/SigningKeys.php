<?php

namespace App\SignIn;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The public keys the tokens of the user pool are signed with, read from its JWKS and cached; read again
 * once when a token names a key not seen yet (Cognito rotated its keys).
 */
final readonly class SigningKeys
{
    public function __construct(private HttpClientInterface $httpClient, private CacheInterface $cache, private UserPool $pool)
    {
    }

    /**
     * @return string|null the PEM of the key, null when the pool has no such key
     */
    public function named(string $keyId): ?string
    {
        return $this->published()[$keyId] ?? $this->publishedAgain()[$keyId] ?? null;
    }

    /**
     * @return array<string, string> the PEM of each RSA key, by key id
     */
    private function published(): array
    {
        return $this->cache->get($this->cacheKey(), fn (): array => $this->read());
    }

    /**
     * @return array<string, string>
     */
    private function publishedAgain(): array
    {
        $this->cache->delete($this->cacheKey());

        return $this->published();
    }

    private function cacheKey(): string
    {
        return sprintf('signing_keys.%s', $this->pool->id());
    }

    /**
     * @return array<string, string>
     */
    private function read(): array
    {
        $keys = [];

        foreach ($this->httpClient->request('GET', $this->pool->keysUrl())->toArray()['keys'] ?? [] as $key) {
            if ('RSA' === ($key['kty'] ?? null)) {
                $keys[$key['kid']] = RsaPublicKey::pemOf($key['n'], $key['e']);
            }
        }

        return $keys;
    }
}
