<?php

declare(strict_types=1);

namespace App\Campaigns\AntiCorruption;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownContact;
use Prospecter\Core\Campaigns\Domain\Model\Addressee;
use Prospecter\Core\Campaigns\Domain\Port\Addressees;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\KeptContactNames;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContacts;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContactsService;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\KeptOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContactsService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0144): Campaigns names a contact as PersonalData lets the member read them,
 * by their first organization as Prospecting lists them, each asked about a set of one.
 */
final readonly class PersonalDataAndProspectingAddressees implements Addressees
{
    public function __construct(
        private NameContactsService $nameContactsService,
        private NameOrganizationsOfContactsService $nameOrganizationsOfContactsService,
    ) {
    }

    public function addressee(AccountId $accountId, MemberId $reader, PersonId $contact): Addressee
    {
        $names = new KeptContactNames();
        ($this->nameContactsService)(new NameContacts($reader->value, [$contact->value]), $names);
        $named = $names->names[0] ?? throw UnknownContact::identifiedBy($contact);
        $organizations = new KeptOrganizationsOfContacts();
        ($this->nameOrganizationsOfContactsService)(new NameOrganizationsOfContacts($accountId->value, [$contact->value]), $organizations);

        return new Addressee($named->firstName, $named->lastName, $organizations->contacts[0]->organizationNames[0] ?? null, $named->jobTitle);
    }
}
