<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application;

/**
 * Where the push of an exchange into my calendar at a provider (`google`) stands (ADR 0133): `pending`, `synced` or
 * `failed`, since when, and why: the refusal of the provider (`calendarRefusedThePush`) when failed, my connection
 * to reconnect (`toReconnect`) when pending for it.
 */
final readonly class ExchangeSync
{
    private const string TO_RECONNECT = 'toReconnect';
    private const string PENDING = 'pending';

    public function __construct(
        public string $provider,
        public string $state,
        public ?\DateTimeImmutable $at,
        public ?string $reason,
    ) {
    }

    public static function pending(string $provider, ?\DateTimeImmutable $since, bool $connected): self
    {
        return new self($provider, self::PENDING, $since, $connected ? null : self::TO_RECONNECT);
    }

    /**
     * Pending, it awaits my connection too when it is to reconnect.
     */
    public function withMyConnection(bool $connected): self
    {
        return self::PENDING === $this->state ? self::pending($this->provider, $this->at, $connected) : $this;
    }
}
