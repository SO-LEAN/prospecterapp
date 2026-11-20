<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails;

/**
 * Which organizations the account prospects have these addresses, whatever their case (ADR 0089, 0154): asked about a
 * set, a single address being a set of one.
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
