<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts;

/**
 * Which of these persons are contacts of the account (ADR 0089): asked about a set, a single person being a set
 * of one.
 */
final readonly class WhichAreContacts
{
    /**
     * @param list<string> $personIds
     */
    public function __construct(
        public array $personIds,
        public string $accountId,
    ) {
    }
}
