<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts;

/**
 * Asks which organizations name these contacts of the account (ADR 0089). The query asks about a set. A single
 * contact is a set of one.
 */
final readonly class WhichOrganizationsNameTheseContacts
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
