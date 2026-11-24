<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\CallerResource;
use App\Contacts\Resource\ContactInListResource;
use App\Prospecting\OrganizationReader;
use App\Prospecting\Resource\OrganizationResource;
use Prospecter\Core\PersonalData\Application\Identification\RecognizeCallingContact\KeptCallingContacts;
use Prospecter\Core\PersonalData\Application\Identification\RecognizeCallingContact\RecognizeCallingContact;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\Identification\RecognizeCallingOrganization\KeptCallingOrganizations;
use Prospecter\Core\Prospecting\Application\Identification\RecognizeCallingOrganization\RecognizeCallingOrganization;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Reads who calls from three contexts (ADR 0072). Prospecting gives the organizations that have the number.
 * PersonalData gives the contacts that have it, read unmasked. Prospecting then gives each contact's organizations,
 * and Campaigns gives their tags. The reader reads each organization again by its id, so that it shows the
 * organization's revision (ADR 0187).
 */
final readonly class CallerReader
{
    public function __construct(
        private QueryHandler $recognizeCallingOrganization,
        private QueryHandler $recognizeCallingContact,
        private OrganizationsOfContacts $organizationsOfContacts,
        private TagsOfContacts $tagsOfContacts,
        private OrganizationReader $organizationReader,
        private ContactInListResourceFactory $contactInListResourceFactory,
    ) {
    }

    public function recognize(string $memberId, string $phoneNumber): CallerResource
    {
        $organizations = new KeptCallingOrganizations();
        $this->recognizeCallingOrganization->__invoke(new RecognizeCallingOrganization($memberId, $phoneNumber), $organizations);
        $callingContacts = new KeptCallingContacts();
        $this->recognizeCallingContact->__invoke(new RecognizeCallingContact($memberId, $phoneNumber), $callingContacts);
        $contacts = $callingContacts->contacts;
        $contactIds = array_map(static fn (PersonCard $contact): string => $contact->id, $contacts);
        $organizationsOfContacts = $this->organizationsOfContacts->read($memberId, $contactIds);
        $tagsOfContacts = $this->tagsOfContacts->read($memberId, $contactIds);

        return new CallerResource(
            array_map(fn (OrganizationSheet $organization): OrganizationResource => $this->organizationReader->read($organization->id), $organizations->organizations),
            array_map(fn (PersonCard $contact): ContactInListResource => $this->contactInListResourceFactory->create($contact, $organizationsOfContacts[$contact->id] ?? [], $tagsOfContacts[$contact->id] ?? []), $contacts),
        );
    }
}
