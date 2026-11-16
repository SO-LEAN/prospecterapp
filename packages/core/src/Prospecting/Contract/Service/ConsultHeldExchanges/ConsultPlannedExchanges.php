<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * The exchanges a member of an account planned and still holds from a moment on (ADR 0089, 0133).
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
