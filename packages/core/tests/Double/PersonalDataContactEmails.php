<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\KeptEmailsOfContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\WhichEmailsHaveTheseContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\WhichEmailsHaveTheseContactsService;
use Prospecter\Core\Prospecting\Application\Port\ContactEmailRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089, 0217). PersonalData publishes a contact's e-mail
 * to the member who reads it in clear, asked about a set of one.
 */
final readonly class PersonalDataContactEmails implements ContactEmailRetriever
{
    public function __construct(private WhichEmailsHaveTheseContactsService $whichEmailsHaveTheseContactsService)
    {
    }

    public function emailOf(MemberId $reader, ContactId $contact): ?string
    {
        $kept = new KeptEmailsOfContacts();
        ($this->whichEmailsHaveTheseContactsService)(new WhichEmailsHaveTheseContacts($reader->value, [$contact->value]), $kept);

        return array_key_exists($contact->value, $kept->emails) ? $kept->emails[$contact->value] : throw UnknownContact::identifiedBy($contact);
    }
}
