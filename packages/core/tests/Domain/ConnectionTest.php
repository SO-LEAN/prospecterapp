<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Event\Connected;
use Prospecter\Core\Connectors\Domain\Event\ConnectionLost;
use Prospecter\Core\Connectors\Domain\Event\Disconnected;
use Prospecter\Core\Connectors\Domain\Model\CalendarEvent;
use Prospecter\Core\Connectors\Domain\Model\Channel;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\Core\Double\InMemoryProviderAccess;
use Tests\Core\Double\InMemoryProviderCalendar;

/**
 * Each gesture on a connection records what happened; an authorization waits ten minutes, not one more; a push
 * reaches the calendar only through the client its token was granted to, while connected.
 */
final class ConnectionTest extends TestCase
{
    private const string EXCHANGE = '0190a1c0-0000-7000-8000-0000000000ee';
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string MEMBER = '0190a1c0-0000-7000-8000-0000000000aa';
    private const string AT = '2026-11-14 10:00';

    public function testAConnectionEstablishedRecordsIt(): void
    {
        $connection = $this->establish();

        $this->assertEquals([new Connected(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT))], $connection->releaseEvents());
    }

    public function testAConnectionRenewedRecordsIt(): void
    {
        $connection = $this->establish();
        $connection->releaseEvents();

        $connection->renew(self::grant(), self::client(), new InMemoryProviderAccess(), new \DateTimeImmutable(self::AT));

        $this->assertEquals([new Connected(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT))], $connection->releaseEvents());
    }

    public function testAConnectionStillGrantedRecordsNothing(): void
    {
        $connection = $this->establish();
        $connection->releaseEvents();

        $connection->check(self::client(), new InMemoryProviderAccess(), new \DateTimeImmutable(self::AT));

        $this->assertSame([], $connection->releaseEvents());
    }

    public function testAConnectionOfAnotherClientRecordsTheConnectionLostOnce(): void
    {
        $connection = $this->establish();
        $connection->releaseEvents();
        $anotherClient = new ClientCredentials(Provider::Google, 'another-client', 'its-secret');

        $connection->check($anotherClient, new InMemoryProviderAccess(), new \DateTimeImmutable(self::AT));
        $connection->check($anotherClient, new InMemoryProviderAccess(), new \DateTimeImmutable(self::AT));

        $this->assertEquals([new ConnectionLost(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT))], $connection->releaseEvents());
    }

    public function testATokenRefusedRecordsTheConnectionLostOnce(): void
    {
        $google = new InMemoryProviderAccess();
        $grant = $google->exchange(self::client(), $google->grant('michel@exemple.fr'), 'a-verifier');
        $connection = Connection::establish(new MemberId(self::MEMBER), new AccountId(self::ACCOUNT), self::client(), $grant, new \DateTimeImmutable(self::AT));
        $connection->releaseEvents();
        $google->refuseEveryToken();

        $connection->check(self::client(), $google, new \DateTimeImmutable(self::AT));
        $connection->check(self::client(), $google, new \DateTimeImmutable(self::AT));

        $this->assertEquals([new ConnectionLost(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT))], $connection->releaseEvents());
    }

    public function testADisconnectionRecordsIt(): void
    {
        $connection = $this->establish();
        $connection->releaseEvents();

        $connection->disconnect(new InMemoryProviderAccess(), new \DateTimeImmutable(self::AT));

        $this->assertEquals([new Disconnected(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT))], $connection->releaseEvents());
    }

    public function testAPushThroughAnotherClientPushesNothingAndRecordsTheConnectionLostOnce(): void
    {
        $calendars = new InMemoryProviderCalendar(new InMemoryProviderAccess());
        $connection = $this->establish();
        $connection->releaseEvents();
        $anotherClient = new ClientCredentials(Provider::Google, 'another-client', 'its-secret');

        $connection->push(self::event(), $anotherClient, $calendars, new \DateTimeImmutable(self::AT));
        $connection->push(self::event(), $anotherClient, $calendars, new \DateTimeImmutable(self::AT));

        $this->assertSame([], $calendars->events());
        $this->assertEquals([new ConnectionLost(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT))], $connection->releaseEvents());
    }

    public function testAPushIsKeptInTheCalendarCreatedOnce(): void
    {
        $calendars = new InMemoryProviderCalendar(new InMemoryProviderAccess());
        $connection = $this->establish();

        $connection->push(self::event(), self::client(), $calendars, new \DateTimeImmutable(self::AT));
        $connection->push(self::event(), self::client(), $calendars, new \DateTimeImmutable(self::AT));

        $this->assertSame([self::EXCHANGE], array_keys($calendars->events()));
        $this->assertSame('calendar-2', $calendars->create(self::client(), new RefreshToken('a-token')), 'One calendar was expected to be created before');
    }

    public function testNothingIsWithdrawnBeforeAPush(): void
    {
        $calendars = new InMemoryProviderCalendar(new InMemoryProviderAccess());
        $connection = $this->establish();
        $connection->releaseEvents();

        $connection->withdraw(new ExchangeId(self::EXCHANGE), self::client(), $calendars, new \DateTimeImmutable(self::AT));

        $this->assertFalse($calendars->wasWithdrawn(self::EXCHANGE));
        $this->assertSame([], $connection->releaseEvents());
    }

    public function testAnEventWithdrawnLeavesTheCalendar(): void
    {
        $calendars = new InMemoryProviderCalendar(new InMemoryProviderAccess());
        $connection = $this->establish();
        $connection->push(self::event(), self::client(), $calendars, new \DateTimeImmutable(self::AT));

        $connection->withdraw(new ExchangeId(self::EXCHANGE), self::client(), $calendars, new \DateTimeImmutable(self::AT));

        $this->assertSame([], $calendars->events());
        $this->assertTrue($calendars->wasWithdrawn(self::EXCHANGE));
    }

    public function testAConnectionChangedByAPushIsStillTheSame(): void
    {
        $read = $this->establish();
        $pushed = clone $read;
        $pushed->push(self::event(), self::client(), new InMemoryProviderCalendar(new InMemoryProviderAccess()), new \DateTimeImmutable('2026-11-14 10:05'));

        $this->assertTrue($pushed->isTheSameAs($read));
    }

    public function testAConnectionMadeAgainIsAnother(): void
    {
        $read = $this->establish();
        $renewed = clone $read;
        $renewed->renew(self::grant(), self::client(), new InMemoryProviderAccess(), new \DateTimeImmutable('2026-11-14 10:05'));

        $this->assertFalse($read->isTheSameAs($renewed));
        $this->assertFalse($read->isTheSameAs(Connection::establish(new MemberId(self::MEMBER), new AccountId(self::ACCOUNT), self::client(), self::grant(), new \DateTimeImmutable('2026-11-14 10:05'))));
    }

    public function testTheConnectionOfAnotherMemberIsAnother(): void
    {
        $another = Connection::establish(new MemberId('0190a1c0-0000-7000-8000-0000000000bb'), new AccountId(self::ACCOUNT), self::client(), self::grant(), new \DateTimeImmutable(self::AT));

        $this->assertFalse($this->establish()->isTheSameAs($another));
    }

    public function testAnAuthorizationWaitsTenMinutes(): void
    {
        $authorization = PendingAuthorization::askedBy(new MemberId(self::MEMBER), Provider::Google, 'a-state', 'a-verifier', new \DateTimeImmutable(self::AT));

        $this->assertTrue($authorization->awaits(new MemberId(self::MEMBER), new \DateTimeImmutable('2026-11-14 10:09:59')));
        $this->assertFalse($authorization->awaits(new MemberId(self::MEMBER), new \DateTimeImmutable('2026-11-14 10:10:00')));
    }

    private function establish(): Connection
    {
        return Connection::establish(new MemberId(self::MEMBER), new AccountId(self::ACCOUNT), self::client(), self::grant(), new \DateTimeImmutable(self::AT));
    }

    private static function client(): ClientCredentials
    {
        return new ClientCredentials(Provider::Google, 'the-client', 'its-secret');
    }

    private static function event(): CalendarEvent
    {
        $at = new \DateTimeImmutable('2026-11-17 14:00');

        return new CalendarEvent(new ExchangeId(self::EXCHANGE), Channel::VideoCall, $at, $at->modify('+30 minutes'), null, null, null, new OrganizationId(self::ACCOUNT), 'ACME', 10);
    }

    private static function grant(): Grant
    {
        return new Grant(new Email('michel@exemple.fr'), new RefreshToken('a-token'), true);
    }
}
