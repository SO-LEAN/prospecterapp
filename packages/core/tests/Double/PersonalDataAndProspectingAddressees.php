<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownContact;
use Prospecter\Core\Campaigns\Domain\Model\Addressee;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Port\Addressees;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\KeptContactNames;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContacts;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContactsService;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\KeptOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContactsService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The API's anticorruption layer, with the same code. Campaigns names a contact as PersonalData lets the member read
 * them. It adds their first organization, as Prospecting lists them. Each one is asked about a set of one.
 */
final readonly class PersonalDataAndProspectingAddressees implements Addressees
{
    public function __construct(
        private NameContactsService $nameContactsService,
        private NameOrganizationsOfContactsService $nameOrganizationsOfContactsService,
    ) {
    }

    public function addressee(AccountId $accountId, MemberId $reader, ContactId $contact): Addressee
    {
        $names = new KeptContactNames();
        ($this->nameContactsService)(new NameContacts($reader->value, [$contact->value]), $names);
        $named = $names->names[0] ?? throw UnknownContact::identifiedBy($contact);
        $organizations = new KeptOrganizationsOfContacts();
        ($this->nameOrganizationsOfContactsService)(new NameOrganizationsOfContacts($accountId->value, [$contact->value]), $organizations);

        return new Addressee($named->firstName, $named->lastName, $named->civility, $organizations->contacts[0]->organizationNames[0] ?? null, $named->jobTitle);
    }
}
