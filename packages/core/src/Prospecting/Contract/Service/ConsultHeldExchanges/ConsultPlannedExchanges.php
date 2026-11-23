<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * Asks for the exchanges that an account's member planned and still holds, from a given moment on (ADR 0089, 0133).
 */
final readonly class ConsultPlannedExchanges
{
    public function __construct(
        public string $accountId,
        public string $holderId,
        public \DateTimeImmutable $from,
    ) {
    }
}
