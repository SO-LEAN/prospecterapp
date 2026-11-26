<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\CallerResource;
use App\Prospecting\OrganizationReader;
use App\Prospecting\Resource\OrganizationResource;
use Prospecter\Core\PersonalData\Application\Identification\RecognizeCallingContact\KeptCallingContacts;
use Prospecter\Core\PersonalData\Application\Identification\RecognizeCallingContact\RecognizeCallingContact;
use Prospecter\Core\Prospecting\Application\Identification\RecognizeCallingOrganization\KeptCallingOrganizations;
use Prospecter\Core\Prospecting\Application\Identification\RecognizeCallingOrganization\RecognizeCallingOrganization;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Composes who calls (ADR 0072, 0217). Prospecting recognizes the organizations that have the number, each read again
 * by its id so that it shows its revision (ADR 0187). PersonalData recognizes the contacts that have it, read unmasked.
 * Prospecting and Campaigns then give each contact's organizations and tags, joined by the contacts' ids.
 */
final readonly class CallerComposition
{
    public function __construct(
        private QueryHandler $recognizeCallingOrganization,
        private QueryHandler $recognizeCallingContact,
        private OrganizationReader $organizationReader,
        private ContactsInList $contactsInList,
    ) {
    }

    public function recognize(string $memberId, string $phoneNumber): CallerResource
    {
        $organizations = new KeptCallingOrganizations();
        $this->recognizeCallingOrganization->__invoke(new RecognizeCallingOrganization($memberId, $phoneNumber), $organizations);
        $contacts = new KeptCallingContacts();
        $this->recognizeCallingContact->__invoke(new RecognizeCallingContact($memberId, $phoneNumber), $contacts);

        return new CallerResource(
            array_map(fn (OrganizationSheet $organization): OrganizationResource => $this->organizationReader->read($organization->id), $organizations->organizations),
            array_values($this->contactsInList->ofCards($memberId, $contacts->contacts)),
        );
    }
}
