<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts;

/**
 * Reads each contact's organizations in one query, for a screen that shows many contacts.
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
