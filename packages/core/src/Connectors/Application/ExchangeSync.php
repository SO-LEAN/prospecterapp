<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application;

/**
 * Where an exchange's push into my calendar at a provider (`google`) stands (ADR 0133). The state is `pending`,
 * `synced` or `failed`. It also says since when, and why. A failed push gives the provider's refusal
 * (`calendarRefusedThePush`). A push may be pending because my connection is to reconnect (`toReconnect`).
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
     * A pending push also waits for my connection when the connection is to reconnect.
     */
    public function withMyConnection(bool $connected): self
    {
        return self::PENDING === $this->state ? self::pending($this->provider, $this->at, $connected) : $this;
    }
}
