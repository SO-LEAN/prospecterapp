<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: the name of a contact as a member reads it, in clear or masked, translated from the context that keeps the
 * personal data (PersonalData), through its published service `NameContactsService` (ADR 0089, 0127).
 */
interface ContactNames
{
    /**
     * None for a contact forgotten, or that the member does not reach.
     */
    public function nameOf(PersonId $contact, MemberId $readBy): ?string;

    /**
     * Their e-mail and their phone as the member reads them in clear; none for a contact forgotten, that the member
     * does not reach, or reads masked.
     */
    public function contactDetailsOf(PersonId $contact, MemberId $readBy): ?ContactDetails;
}
