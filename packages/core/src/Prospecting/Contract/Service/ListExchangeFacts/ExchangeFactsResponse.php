<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts;

/**
 * The facts of an exchange, what another context counts of it: whether the account keeps it, where it stands
 * (`planned`, `done`, `missed`, `cancelled`), the moment it happens, and the revision of its state, incremented at
 * each change.
 */
final readonly class ExchangeFactsResponse
{
    private const int NO_REVISION = 0;

    public function __construct(
        public string $exchangeId,
        public int $revision,
        public bool $present,
        public ?string $status,
        public ?\DateTimeImmutable $notedAt,
    ) {
    }

    /**
     * An erased exchange: the last of its facts.
     */
    public static function absent(string $exchangeId): self
    {
        return new self($exchangeId, self::NO_REVISION, false, null, null);
    }
}
