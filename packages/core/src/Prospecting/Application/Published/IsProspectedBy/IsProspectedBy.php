<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Published\IsProspectedBy;

/**
 * Published to the other contexts (ADR 0045): whether an organization is one the account prospects.
 */
final readonly class IsProspectedBy
{
    public function __construct(
        public string $organizationId,
        public string $accountId,
    ) {
    }
}
