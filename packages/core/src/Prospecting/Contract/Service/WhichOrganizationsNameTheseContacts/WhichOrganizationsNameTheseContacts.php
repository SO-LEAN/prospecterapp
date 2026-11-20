<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts;

/**
 * Which organizations name these contacts of the account (ADR 0089): asked about a set, a single contact being a set of
 * one.
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
