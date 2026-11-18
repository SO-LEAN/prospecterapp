<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ProposeOrganizationToAttachOutput extends OutputPort
{
    /**
     * @param ProposedOrganization|null $organization none for a public mailbox, or a domain no organization of the
     *                                                account has
     */
    public function present(?ProposedOrganization $organization): void;
}
