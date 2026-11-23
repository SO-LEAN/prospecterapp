<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: a contact's name as a member reads it, unmasked or masked (ADR 0089, 0127). It is translated from
 * PersonalData, the context that keeps the personal data, through its published service `NameContactsService`.
 */
interface ContactNames
{
    /**
     * None for a forgotten contact, or for a contact that the member does not reach.
     */
    public function nameOf(PersonId $contact, MemberId $readBy): ?string;

    /**
     * The contact's e-mail and phone as the member reads them unmasked. None for a forgotten contact, for a contact
     * that the member does not reach, or for a contact that the member reads masked.
     */
    public function contactDetailsOf(PersonId $contact, MemberId $readBy): ?ContactDetails;
}
