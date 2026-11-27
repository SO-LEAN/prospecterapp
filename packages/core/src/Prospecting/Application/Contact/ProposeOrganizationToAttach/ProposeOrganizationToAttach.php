<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ProposeOrganizationToAttach;

/**
 * Proposes the account's organization to attach a contact to, by their email's domain (ADR 0146). The client gives
 * the contact. PersonalData gives their e-mail, read in clear. Prospecting keeps no e-mail.
 */
final readonly class ProposeOrganizationToAttach
{
    public function __construct(
        public string $prospectorId,
        public string $contactId,
    ) {
    }
}
