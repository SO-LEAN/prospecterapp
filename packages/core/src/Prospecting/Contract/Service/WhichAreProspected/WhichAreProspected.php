<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected;

/**
 * Asks which of these organizations the account prospects (ADR 0089). The query asks about a set. A single
 * organization is a set of one.
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
