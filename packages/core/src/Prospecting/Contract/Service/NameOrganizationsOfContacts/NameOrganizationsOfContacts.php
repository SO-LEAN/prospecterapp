<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts;

/**
 * Asks for the organizations that name these contacts of the account (ADR 0089, 0144). The query asks about a set.
 * A single contact is a set of one.
 */
final readonly class NameOrganizationsOfContacts
{
    /**
     * @param list<string> $personIds
     */
    public function __construct(
        public string $accountId,
        public array $personIds,
    ) {
    }
}
