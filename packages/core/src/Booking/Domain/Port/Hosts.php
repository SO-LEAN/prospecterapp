<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Port;

use Prospecter\Core\Booking\Domain\Exception\UnknownMember;
use Prospecter\Core\Booking\Domain\Model\HostIdentity;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the members who host a booking page, their account and who they are to the bookers, translated from the
 * contexts that know them, through their published services: Accounts (`ConsultMembersService`) and PersonalData
 * (`PresentMembersService`) (ADR 0045, 0089, 0153).
 */
interface Hosts
{
    /**
     * @throws UnknownMember
     */
    public function accountOf(MemberId $host): AccountId;

    /**
     * @throws UnknownMember
     */
    public function identityOf(MemberId $host): HostIdentity;

    /**
     * Whether the host may hold the contacts their bookers become (ADR 0146, 0164).
     */
    public function mayHoldBookers(MemberId $host): bool;
}
