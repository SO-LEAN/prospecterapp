<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownContact;
use Prospecter\Core\Campaigns\Domain\Model\AddresseeName;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Port: how a contact is named, as the member may read them (ADR 0089, 0144, 0223). The adapter translates it from
 * PersonalData's published service `NameContactsService`.
 */
interface AddresseeNames
{
    /**
     * @throws UnknownContact when the member reads no such contact
     */
    public function nameOf(MemberId $reader, ContactId $contact): AddresseeName;
}
