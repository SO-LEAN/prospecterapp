<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails;

/**
 * Asks which organizations the account prospects have these addresses, ignoring case (ADR 0089, 0154). The query
 * asks about a set. A single address is a set of one.
 */
final readonly class WhichOrganizationsHaveTheseEmails
{
    /**
     * @param list<string> $emails
     */
    public function __construct(
        public array $emails,
        public string $accountId,
    ) {
    }
}
