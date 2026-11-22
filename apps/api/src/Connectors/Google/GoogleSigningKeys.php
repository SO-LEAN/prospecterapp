<?php

declare(strict_types=1);

namespace App\Connectors\Google;

use App\Shared\Typed;
use App\SignIn\RsaPublicKey;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The public keys Google signs its tokens with, read from its JWKS and cached, as those of the user pool are; read
 * again once when a token names a key not seen yet, Google rotating its keys (ADR 0154).
 */
final readonly class GoogleSigningKeys
{
    private const string CACHE_KEY = 'google_signing_keys';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        #[Autowire(env: 'GOOGLE_CERTS_URL')]
        private string $certsUrl,
    ) {
    }

    /**
     * @return string|null the PEM of the key, null when Google has no such key
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
        return $this->cache->get(self::CACHE_KEY, fn (): array => $this->read());
    }

    /**
     * @return array<string, string>
     */
    private function publishedAgain(): array
    {
        $this->cache->delete(self::CACHE_KEY);

        return $this->published();
    }

    /**
     * @return array<string, string>
     */
    private function read(): array
    {
        $keys = [];

        foreach (Typed::documents($this->httpClient->request('GET', $this->certsUrl)->toArray()['keys'] ?? []) as $key) {
            if ('RSA' === ($key['kty'] ?? null)) {
                $keys[Typed::string($key['kid'])] = RsaPublicKey::pemOf(Typed::string($key['n']), Typed::string($key['e']));
            }
        }

        return $keys;
    }
}
