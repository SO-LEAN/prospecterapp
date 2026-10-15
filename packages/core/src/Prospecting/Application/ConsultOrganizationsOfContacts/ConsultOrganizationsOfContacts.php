<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts;

/**
 * The organizations of each of these contacts, at once, for a screen showing many contacts.
 */
final readonly class ConsultOrganizationsOfContacts
{
    /**
     * @param list<string> $personIds
     */
    public function __construct(
        public string $prospectorId,
        public array $personIds,
    ) {
    }
}
