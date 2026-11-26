<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\PersonDescription;

/**
 * An organization's contacts, in the order they were attached, as PersonalData lets the reader read them. A contact
 * that PersonalData no longer reads is left out. The organization tells what the reader may do with its contacts.
 */
final readonly class OrganizationContacts
{
    /**
     * @param list<PersonDescription> $contacts
     */
    public function __construct(
        public OrganizationSheet $organization,
        public array $contacts,
    ) {
    }
}
