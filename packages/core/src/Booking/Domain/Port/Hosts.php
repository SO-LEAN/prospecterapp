<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Port;

use Prospecter\Core\Booking\Domain\Exception\UnknownMember;
use Prospecter\Core\Booking\Domain\Model\HostIdentity;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Port: the members who host a booking page, their account, and how they present themselves to bookers. The adapter
 * translates them from the contexts that know them, through their published services: Accounts
 * (`ConsultMembersService`) and PersonalData (`PresentMembersService`) (ADR 0045, 0089, 0153).
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
     * Whether the host can hold the contacts that their bookers become (ADR 0146, 0164).
     */
    public function mayHoldBookers(MemberId $host): bool;
}
