<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations;

/**
 * Asks for the addresses of these organizations the account prospects, to catch up the e-mails exchanged with them.
 * The query asks about a set. A single organization is a set of one (ADR 0089).
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
