<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails;

/**
 * Which contacts of the account have these addresses, whatever their case (ADR 0089, 0154): asked about a set, a
 * single address being a set of one.
 */
final readonly class WhichContactsHaveTheseEmails
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
