<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts;

/**
 * Which of these persons are the account's contacts (ADR 0089). It asks about a set. A single person is a set of one.
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
