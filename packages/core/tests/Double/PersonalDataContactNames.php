<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\Connectors\Domain\Port\ContactNames;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\KeptContactNames;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContacts;
use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContactsService;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089). Connectors names a contact as the member reads
 * them in PersonalData. It asks about a set of one (ADR 0127).
 */
final readonly class PersonalDataContactNames implements ContactNames
{
    public function __construct(private NameContactsService $nameContactsService)
    {
    }

    public function nameOf(ContactId $contact, MemberId $readBy): ?string
    {
        $kept = new KeptContactNames();
        ($this->nameContactsService)(new NameContacts($readBy->value, [$contact->value]), $kept);
        $name = $kept->names[0] ?? null;

        return null === $name ? null : trim(sprintf('%s %s', $name->firstName, $name->lastName));
    }

    public function contactDetailsOf(ContactId $contact, MemberId $readBy): ?ContactDetails
    {
        $kept = new KeptContactNames();
        ($this->nameContactsService)(new NameContacts($readBy->value, [$contact->value]), $kept);
        $named = $kept->names[0] ?? null;

        return null === $named ? null : new ContactDetails(Email::orNone($named->email), PhoneNumber::orNone($named->phoneNumber));
    }
}
