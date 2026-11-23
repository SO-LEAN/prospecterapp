<?php

declare(strict_types=1);

namespace App\Connectors\Google;

use App\Shared\Typed;
use App\SignIn\RsaPublicKey;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The public keys that Google signs its tokens with. They are read from Google's JWKS and cached, like the user pool's
 * keys. Google rotates its keys, so they are read again once when a token names a key not seen yet (ADR 0154).
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
     * @return string|null the key's PEM, or null when Google has no such key
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
