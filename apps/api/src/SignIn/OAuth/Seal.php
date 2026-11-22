<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * What the facade signing the AIs in must find again travels sealed, since it keeps nothing (ADR 0157): encrypted and
 * authenticated with a key only the API knows (`sodium_crypto_secretbox`), for one purpose, for a while.
 */
final readonly class Seal
{
    private const int BASE64 = SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING;

    public function __construct(
        #[Autowire(env: 'base64:OAUTH_SEAL_KEY')]
        #[\SensitiveParameter]
        private string $key,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param array<string, string> $content
     */
    public function close(array $content, SealPurpose $purpose): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $until = $this->clock->now()->modify($purpose->lifetime())->getTimestamp();
        $message = json_encode(['for' => $purpose->value, 'until' => $until, 'content' => $content], JSON_THROW_ON_ERROR);

        return sodium_bin2base64($nonce.sodium_crypto_secretbox($message, $nonce, $this->key), self::BASE64);
    }

    /**
     * @return array<string, string>
     *
     * @throws BrokenSeal
     */
    public function open(string $sealed, SealPurpose $purpose): array
    {
        $envelope = $this->envelopeOf($sealed);

        if ($purpose->value !== $envelope['for'] || $this->clock->now()->getTimestamp() > $envelope['until']) {
            throw BrokenSeal::given();
        }

        return $envelope['content'];
    }

    /**
     * @return array{for: string, until: int, content: array<string, string>}
     */
    private function envelopeOf(string $sealed): array
    {
        try {
            $bytes = sodium_base642bin($sealed, self::BASE64);
            $message = sodium_crypto_secretbox_open(substr($bytes, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($bytes, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $this->key);
        } catch (\SodiumException) {
            throw BrokenSeal::given();
        }

        return false === $message ? throw BrokenSeal::given() : json_decode($message, true, flags: JSON_THROW_ON_ERROR);
    }
}
