<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\UnknownContact;
use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Port: a contact's address as the member reads it (ADR 0089, 0223). It is translated from PersonalData's published
 * service `WhichEmailsHaveTheseContactsService`. The address is read to search the mailbox, and is never kept.
 */
interface ContactAddresses
{
    /**
     * None when the contact has no address.
     *
     * @throws UnknownContact unless the member reads the contact unmasked
     */
    public function of(MemberId $reader, ContactId $contact): ?Email;
}
