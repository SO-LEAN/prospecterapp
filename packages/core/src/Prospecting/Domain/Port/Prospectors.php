<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownProspector;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Port: the prospector a member is. It is translated from Accounts, the context that knows an account's members,
 * through its published service `ConsultMembersService` (ADR 0045, 0089).
 */
interface Prospectors
{
    /**
     * @throws UnknownProspector
     */
    public function prospectorFrom(MemberId $id): Prospector;
}
