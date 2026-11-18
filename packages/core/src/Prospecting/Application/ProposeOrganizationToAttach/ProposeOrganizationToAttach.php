<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach;

/**
 * Which organization of the account to attach a contact to, proposed by the domain of their email (ADR 0146): the
 * email as the client read it, Prospecting keeping none.
 */
final readonly class ProposeOrganizationToAttach
{
    public function __construct(
        public string $prospectorId,
        public string $email,
    ) {
    }
}
