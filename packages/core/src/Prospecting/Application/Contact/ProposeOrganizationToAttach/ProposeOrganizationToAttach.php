<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ProposeOrganizationToAttach;

/**
 * Proposes the account's organization to attach a contact to, by their email's domain (ADR 0146). The client gives
 * the email as it read it. Prospecting keeps no email.
 */
final readonly class ProposeOrganizationToAttach
{
    public function __construct(
        public string $prospectorId,
        public string $email,
    ) {
    }
}
