<?php

declare(strict_types=1);

namespace App\SignIn;

use App\Shared\Typed;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The public keys that sign the user pool's tokens, read from its JWKS and cached. When a token names an unknown key,
 * they are read again once (Cognito rotated its keys).
 */
final readonly class SigningKeys
{
    public function __construct(private HttpClientInterface $httpClient, private CacheInterface $cache, private UserPool $pool)
    {
    }

    /**
     * @return string|null the key's PEM, null when the pool has no such key
     */
    public function named(string $keyId): ?string
    {
        return $this->published()[$keyId] ?? $this->publishedAgain()[$keyId] ?? null;
    }

    /**
     * @return array<string, string> each RSA key's PEM, by key id
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

        foreach (Typed::documents($this->httpClient->request('GET', $this->pool->keysUrl())->toArray()['keys'] ?? []) as $key) {
            if ('RSA' === ($key['kty'] ?? null)) {
                $keys[Typed::string($key['kid'])] = RsaPublicKey::pemOf(Typed::string($key['n']), Typed::string($key['e']));
            }
        }

        return $keys;
    }
}
