<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Exception\UnknownContact;
use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\Connectors\Domain\Port\ContactAddresses;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\KeptEmailsOfContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\WhichEmailsHaveTheseContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\WhichEmailsHaveTheseContactsService;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089, 0223). Connectors gets a contact's address from PersonalData. It asks
 * about a set of one. It searches the mailbox by the address and never keeps it.
 */
final readonly class PersonalDataContactAddresses implements ContactAddresses
{
    public function __construct(private WhichEmailsHaveTheseContactsService $whichEmailsHaveTheseContactsService)
    {
    }

    public function of(MemberId $reader, ContactId $contact): ?Email
    {
        $kept = new KeptEmailsOfContacts();
        ($this->whichEmailsHaveTheseContactsService)(new WhichEmailsHaveTheseContacts($reader->value, [$contact->value]), $kept);

        if (!array_key_exists($contact->value, $kept->emails)) {
            throw UnknownContact::withId($contact);
        }

        $address = $kept->emails[$contact->value];

        return null === $address ? null : new Email($address);
    }
}
