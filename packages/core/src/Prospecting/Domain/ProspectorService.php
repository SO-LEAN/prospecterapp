<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownProspector;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the prospector a member is, translated from the context that knows the members of an account (Accounts),
 * through its published query `ConsultMember` (ADR 0045).
 */
interface ProspectorService
{
    /**
     * @throws UnknownProspector
     */
    public function prospectorFrom(MemberId $id): Prospector;
}
