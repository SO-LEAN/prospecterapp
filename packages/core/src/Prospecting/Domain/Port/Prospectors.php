<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownProspector;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the prospector a member is, translated from the context that knows the members of an account (Accounts),
 * through its published query `ConsultMember` (ADR 0045).
 */
interface Prospectors
{
    /**
     * @throws UnknownProspector
     */
    public function prospectorFrom(MemberId $id): Prospector;
}
