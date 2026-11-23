<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails;

/**
 * Which of the account's contacts have these addresses, whatever their case (ADR 0089, 0154). It asks about a set. A
 * single address is a set of one.
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
