<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Port;

use Prospecter\Core\Booking\Domain\Exception\UnknownMember;
use Prospecter\Core\Booking\Domain\Model\HostMembership;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Port: the members who host a booking page, as Accounts knows them. The adapter translates them from Accounts'
 * published service `ConsultMembersService` (ADR 0045, 0089, 0153, 0223).
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
    public function membershipOf(MemberId $host): HostMembership;

    /**
     * Whether the host has the right to manage personal data, as Accounts says.
     *
     * @throws UnknownMember
     */
    public function readsPersonalData(MemberId $host): bool;
}
