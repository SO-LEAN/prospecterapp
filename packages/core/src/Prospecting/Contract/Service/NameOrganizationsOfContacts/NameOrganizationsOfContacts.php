<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts;

/**
 * The organizations naming these contacts of the account (ADR 0089, 0144): asked about a set, a single contact being a
 * set of one.
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
