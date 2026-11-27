<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Service;

use Prospecter\Core\Booking\Domain\Exception\UnknownMember;
use Prospecter\Core\Booking\Domain\Model\HostIdentity;
use Prospecter\Core\Booking\Domain\Port\HostPresentations;
use Prospecter\Core\Booking\Domain\Port\Hosts;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * How a host presents themselves to the people they give their link to: their name and picture from PersonalData,
 * and their account's organization from Accounts (ADR 0153, 0223).
 */
final readonly class HostIdentities
{
    public function __construct(
        private Hosts $hosts,
        private HostPresentations $presentations,
    ) {
    }

    /**
     * @throws UnknownMember
     */
    public function identityOf(MemberId $host): HostIdentity
    {
        $membership = $this->hosts->membershipOf($host);
        $presented = $this->presentations->presentationOf($membership->personId);

        return new HostIdentity($presented?->firstName, $presented?->lastName, $presented?->pictureUrl, $membership->accountName);
    }
}
