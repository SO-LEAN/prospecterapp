<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Event\Connected;
use Prospecter\Core\Connectors\Domain\Event\ConnectionLost;
use Prospecter\Core\Connectors\Domain\Event\Disconnected;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Double\InMemoryProviderAccess;

/**
 * Each gesture on a connection records what happened; an authorization waits ten minutes, not one more.
 */
final class ConnectionTest extends TestCase
{
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

        $connection->renew(self::grant(), new InMemoryProviderAccess(), new \DateTimeImmutable(self::AT));

        $this->assertEquals([new Connected(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT))], $connection->releaseEvents());
    }

    public function testAConnectionStillGrantedRecordsNothing(): void
    {
        $connection = $this->establish();
        $connection->releaseEvents();

        $connection->check(new InMemoryProviderAccess(), new \DateTimeImmutable(self::AT));

        $this->assertSame([], $connection->releaseEvents());
    }

    public function testATokenRefusedRecordsTheConnectionLostOnce(): void
    {
        $google = new InMemoryProviderAccess();
        $grant = $google->exchange(Provider::Google, $google->grant('michel@exemple.fr'), 'a-verifier');
        $connection = Connection::establish(new MemberId(self::MEMBER), new AccountId(self::ACCOUNT), Provider::Google, $grant, new \DateTimeImmutable(self::AT));
        $connection->releaseEvents();
        $google->refuseEveryToken();

        $connection->check($google, new \DateTimeImmutable(self::AT));
        $connection->check($google, new \DateTimeImmutable(self::AT));

        $this->assertEquals([new ConnectionLost(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT))], $connection->releaseEvents());
    }

    public function testADisconnectionRecordsIt(): void
    {
        $connection = $this->establish();
        $connection->releaseEvents();

        $connection->disconnect(new InMemoryProviderAccess(), new \DateTimeImmutable(self::AT));

        $this->assertEquals([new Disconnected(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT))], $connection->releaseEvents());
    }

    public function testAnAuthorizationWaitsTenMinutes(): void
    {
        $authorization = PendingAuthorization::askedBy(new MemberId(self::MEMBER), Provider::Google, 'a-state', 'a-verifier', new \DateTimeImmutable(self::AT));

        $this->assertTrue($authorization->awaits(new MemberId(self::MEMBER), new \DateTimeImmutable('2026-11-14 10:09:59')));
        $this->assertFalse($authorization->awaits(new MemberId(self::MEMBER), new \DateTimeImmutable('2026-11-14 10:10:00')));
    }

    private function establish(): Connection
    {
        return Connection::establish(new MemberId(self::MEMBER), new AccountId(self::ACCOUNT), Provider::Google, self::grant(), new \DateTimeImmutable(self::AT));
    }

    private static function grant(): Grant
    {
        return new Grant(new Email('michel@exemple.fr'), new RefreshToken('a-token'), true);
    }
}
