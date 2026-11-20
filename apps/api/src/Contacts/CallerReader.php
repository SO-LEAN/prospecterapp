<?php

namespace App\Contacts;

use App\Contacts\Resource\CallerResource;
use App\Contacts\Resource\ContactInListResource;
use App\Prospecting\OrganizationReader;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\RecognizeCallingContact\KeptCallingContacts;
use Prospecter\Core\PersonalData\Application\RecognizeCallingContact\RecognizeCallingContact;
use Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization\KeptCallingOrganizations;
use Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization\RecognizeCallingOrganization;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * Who calls (ADR 0072), composed of three contexts: Prospecting tells the organizations having the number,
 * PersonalData the contacts, read in clear; Prospecting then the organizations of each contact, Campaigns its tags.
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
        ($this->recognizeCallingOrganization)(new RecognizeCallingOrganization($memberId, $phoneNumber), $organizations);
        $callingContacts = new KeptCallingContacts();
        ($this->recognizeCallingContact)(new RecognizeCallingContact($memberId, $phoneNumber), $callingContacts);
        $contacts = $callingContacts->contacts;
        $contactIds = array_map(static fn (PersonCard $contact): string => $contact->id, $contacts);
        $organizationsOfContacts = $this->organizationsOfContacts->read($memberId, $contactIds);
        $tagsOfContacts = $this->tagsOfContacts->read($memberId, $contactIds);

        return new CallerResource(
            array_map($this->organizationReader->resourceOf(...), $organizations->organizations),
            array_map(fn (PersonCard $contact): ContactInListResource => $this->contactInListResourceFactory->create($contact, $organizationsOfContacts[$contact->id] ?? [], $tagsOfContacts[$contact->id] ?? []), $contacts),
        );
    }
}
