<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ProposeOrganizationToAttach;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptProposedOrganization implements ProposeOrganizationToAttachOutput
{
    public private(set) ?ProposedOrganization $organization = null;

    public function present(?ProposedOrganization $organization): void
    {
        $this->organization = $organization;
    }
}
