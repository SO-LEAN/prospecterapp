<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * An organization's contacts, by their ids, in the order they were attached. A contact that PersonalData no longer
 * knows is left out. The organization tells what the reader may do with its contacts.
 */
final readonly class OrganizationContacts
{
    /**
     * @param list<string> $contactIds
     */
    public function __construct(
        public OrganizationSheet $organization,
        public array $contactIds,
    ) {
    }
}
