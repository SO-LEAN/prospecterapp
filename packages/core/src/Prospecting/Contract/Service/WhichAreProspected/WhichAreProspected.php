<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected;

/**
 * Which of these organizations the account prospects (ADR 0089): asked about a set, a single organization being
 * a set of one.
 */
final readonly class WhichAreProspected
{
    /**
     * @param list<string> $organizationIds
     */
    public function __construct(
        public array $organizationIds,
        public string $accountId,
    ) {
    }
}
