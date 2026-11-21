<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations;

/**
 * Which addresses these organizations the account prospects have: asked about a set, a single organization being a
 * set of one (ADR 0089), to catch up the e-mails exchanged with them.
 */
final readonly class WhichEmailsHaveTheseOrganizations
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
