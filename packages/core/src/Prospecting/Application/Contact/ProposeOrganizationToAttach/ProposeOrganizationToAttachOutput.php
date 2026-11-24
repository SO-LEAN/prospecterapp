<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ProposeOrganizationToAttach;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ProposeOrganizationToAttachOutput extends OutputPort
{
    /**
     * @param ProposedOrganization|null $organization none for a public mailbox, or for a domain that none of the
     *                                                account's organizations has
     */
    public function present(?ProposedOrganization $organization): void;
}
