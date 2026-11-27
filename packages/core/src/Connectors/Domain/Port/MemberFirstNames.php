<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

/**
 * Port: the first name a member presents themselves with (ADR 0089, 0175, 0223). It is translated from PersonalData's
 * published service `PresentMembersService`.
 */
interface MemberFirstNames
{
    /**
     * None when PersonalData does not present the person, or knows no first name.
     */
    public function firstNameOf(string $personId): ?string;
}
