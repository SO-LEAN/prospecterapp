<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownContact;
use Prospecter\Core\Campaigns\Domain\Model\AddresseeName;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Port\AddresseeNames;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\KeptContactNames;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContacts;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContactsService;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The API's anticorruption layer, with the same code (ADR 0223). Campaigns takes a contact's name from what PersonalData lets the
 * member read. It asks about a set of one.
 */
final readonly class PersonalDataAddresseeNames implements AddresseeNames
{
    public function __construct(private NameContactsService $nameContactsService)
    {
    }

    public function nameOf(MemberId $reader, ContactId $contact): AddresseeName
    {
        $names = new KeptContactNames();
        ($this->nameContactsService)(new NameContacts($reader->value, [$contact->value]), $names);
        $named = $names->names[0] ?? throw UnknownContact::identifiedBy($contact);

        return new AddresseeName($named->firstName, $named->lastName, $named->civility, $named->jobTitle);
    }
}
