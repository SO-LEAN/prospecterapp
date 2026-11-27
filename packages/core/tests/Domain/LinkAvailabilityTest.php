<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Application\Member\ConsultMembers\ConsultMembersHandler;
use Prospecter\Core\Booking\Domain\Service\LinkAvailability;
use Prospecter\Core\Connectors\Application\Connection\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendarHandler;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\Core\Double\AccountsHosts;
use Tests\Core\Double\ConnectorsConnectedCalendars;
use Tests\Core\Double\InMemoryConnectionRepository;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryMemberRetriever;

/**
 * Whether an open page's public link offers slots. The scenarios cover the host's calendar and their right. No scenario
 * reaches a host whom Accounts no longer knows.
 */
final class LinkAvailabilityTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-0000000000aa';

    private const string HOST = '0190a1c0-0000-7000-8000-000000000001';

    public function testTheLinkOfAHostWhomAccountsDoesNotKnowOffersNoSlot(): void
    {
        $database = new InMemoryDatabase();
        self::connectTheCalendarOfTheHost($database);
        $links = new LinkAvailability(
            new ConnectorsConnectedCalendars(new WhichHaveAConnectedCalendarHandler(new InMemoryConnectionRepository($database))),
            new AccountsHosts(new ConsultMembersHandler(new InMemoryMemberRetriever($database))),
        );

        $this->assertFalse($links->offersSlots(true, new MemberId(self::HOST)));
    }

    private static function connectTheCalendarOfTheHost(InMemoryDatabase $database): void
    {
        $gesture = new Gesture(new AccountId(self::ACCOUNT), new MemberId(self::HOST), new \DateTimeImmutable('2026-11-14 10:00'));
        $grant = new Grant(new Email('host@exemple.fr'), new RefreshToken('a-token'), Scope::Calendar);
        new InMemoryConnectionRepository($database)->save(Connection::establish($gesture, new ClientCredentials(Provider::Google, 'the-client', 'its-secret'), $grant));
    }
}
