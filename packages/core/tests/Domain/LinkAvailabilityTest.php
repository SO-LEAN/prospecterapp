<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Domain\Exception\UnknownMember;
use Prospecter\Core\Booking\Domain\Model\HostIdentity;
use Prospecter\Core\Booking\Domain\Port\ConnectedCalendars;
use Prospecter\Core\Booking\Domain\Port\Hosts;
use Prospecter\Core\Booking\Domain\Service\LinkAvailability;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Whether an open page's public link offers slots. The scenarios cover the host's calendar and their right. No scenario
 * reaches a host whom Accounts no longer knows.
 */
final class LinkAvailabilityTest extends TestCase
{
    private const string HOST = '0190a1c0-0000-7000-8000-000000000001';

    public function testTheLinkOfAHostWhomAccountsDoesNotKnowOffersNoSlot(): void
    {
        $links = new LinkAvailability(self::connectedCalendars(), self::unknownHosts());

        $this->assertFalse($links->offersSlots(true, new MemberId(self::HOST)));
    }

    private static function connectedCalendars(): ConnectedCalendars
    {
        return new class implements ConnectedCalendars {
            public function isConnectedFor(MemberId $host): bool
            {
                return true;
            }
        };
    }

    private static function unknownHosts(): Hosts
    {
        return new class implements Hosts {
            public function accountOf(MemberId $host): AccountId
            {
                throw UnknownMember::identifiedBy($host);
            }

            public function identityOf(MemberId $host): HostIdentity
            {
                throw UnknownMember::identifiedBy($host);
            }

            public function readsPersonalData(MemberId $host): bool
            {
                throw UnknownMember::identifiedBy($host);
            }
        };
    }
}
