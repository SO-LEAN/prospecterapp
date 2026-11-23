<?php

declare(strict_types=1);

namespace Tests\App\Connectors;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Hydrator;
use App\Shared\Typed;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\MailboxNotifications;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\EmptyMailbox;

/**
 * The connections in the table of Connectors (ADR 0120): a push puts a connection in place of itself while it is
 * still kept, never bringing back one its member disconnected meanwhile (ADR 0133).
 */
class DynamoConnectionRepositoryTest extends ApiTestCase
{
    public function testAConnectionStillKeptIsReplaced(): void
    {
        $connection = self::aConnection();
        $this->connections()->save($connection);

        $this->connections()->replace($connection);

        $this->assertEquals($connection->member(), $this->connections()->get($connection->member(), Provider::Google)->member());
    }

    public function testAConnectionForgottenMeanwhileIsNotBroughtBack(): void
    {
        $connection = self::aConnection();
        $this->connections()->save($connection);
        $this->connections()->remove($connection);

        try {
            $this->connections()->replace($connection);
            $this->fail('A connection forgotten meanwhile was expected to be refused');
        } catch (ConditionFailed) {
            $this->assertNull($this->connections()->find($connection->member(), Provider::Google));
        }
    }

    public function testAConnectionMadeAgainMeanwhileIsNotOverwritten(): void
    {
        $read = self::aConnection();
        $this->connections()->save($read);
        $this->connections()->remove($read);
        $madeAgain = self::aConnection($read->member(), new RefreshToken('a-new-refresh-token'), '2026-10-15 09:05 UTC');
        $this->connections()->save($madeAgain);

        try {
            $this->connections()->replace($read);
            $this->fail('A connection made again meanwhile was expected to be kept');
        } catch (ConditionFailed) {
            $this->assertTrue($madeAgain->isTheSameAs($this->connections()->get($read->member(), Provider::Google)));
        }
    }

    public function testAConnectionImportingTheMailIsRestoredAsSaved(): void
    {
        $connection = self::aConnection();
        $connection->importMail(new ClientCredentials(Provider::Google, 'a-client', 'a-secret'), new MailboxNotifications('projects/solean/topics/gmail', new Email('push@solean.iam.gserviceaccount.com')), self::aMailboxStandingAt('1000'), new \DateTimeImmutable('2026-10-15 09:01 UTC'));

        $this->connections()->save($connection);

        $this->assertEquals(self::importOf($connection), self::importOf($this->connections()->get($connection->member(), Provider::Google)));
    }

    public function testAChangeOfTheMailboxNotedIsRestoredAsSaved(): void
    {
        $connection = self::aConnectionImportingTheMail();
        $connection->noteMailboxChange('1010', new \DateTimeImmutable('2026-10-15 09:03 UTC'));

        $this->connections()->save($connection);

        $this->assertEquals(self::importOf($connection), self::importOf($this->connections()->get($connection->member(), Provider::Google)));
    }

    public function testAConnectionImportingTheMailIsFoundByItsMailboxInItsAccount(): void
    {
        $connection = self::aConnectionImportingTheMail();
        $this->connections()->save($connection);
        $account = Typed::object(Hydrator::stateOf($connection)['accountId'], AccountId::class);

        $this->assertTrue($connection->isTheSameAs($this->connections()->importing($account, Provider::Google, new Email('michel@exemple.fr')) ?? self::fail('The connection importing the mailbox was expected')));
        $this->assertNull($this->connections()->importing(new AccountId(self::newIdentity()), Provider::Google, new Email('michel@exemple.fr')), 'Another account imports nothing of the mailbox');
    }

    public function testAConnectionWhoseImportStoppedIsNoLongerFoundByItsMailbox(): void
    {
        $connection = self::aConnectionImportingTheMail();
        $this->connections()->save($connection);
        $connection->stopMailImport(null, self::aMailboxStandingAt('1000'), new \DateTimeImmutable('2026-10-15 09:02 UTC'));

        $this->connections()->save($connection);

        $this->assertNull($this->connections()->importing(Typed::object(Hydrator::stateOf($connection)['accountId'], AccountId::class), Provider::Google, new Email('michel@exemple.fr')));
    }

    private static function aConnectionImportingTheMail(): Connection
    {
        $connection = self::aConnection();
        $connection->importMail(new ClientCredentials(Provider::Google, 'a-client', 'a-secret'), new MailboxNotifications('projects/solean/topics/gmail', new Email('push@solean.iam.gserviceaccount.com')), self::aMailboxStandingAt('1000'), new \DateTimeImmutable('2026-10-15 09:01 UTC'));

        return $connection;
    }

    /**
     * @return array<string, mixed>
     */
    private static function importOf(Connection $connection): array
    {
        return array_intersect_key(Hydrator::stateOf($connection), array_flip(['mailImport', 'historyId', 'watchExpiresAt', 'notifiedHistoryId', 'lastNotifiedAt', 'historyReadAt', 'modifiedAt']));
    }

    private static function aMailboxStandingAt(string $historyId): ProviderMailbox
    {
        return new EmptyMailbox($historyId);
    }

    private static function aConnection(?MemberId $member = null, RefreshToken $token = new RefreshToken('a-refresh-token'), string $at = '2026-10-15 09:00 UTC'): Connection
    {
        $grant = new Grant(new Email('michel@exemple.fr'), $token, Scope::Calendar, Scope::Mailbox);

        return Connection::establish(new Gesture(new AccountId(self::newIdentity()), $member ?? new MemberId(self::newIdentity()), new \DateTimeImmutable($at)), new ClientCredentials(Provider::Google, 'a-client', 'a-secret'), $grant);
    }

    private function connections(): ConnectionRepository
    {
        return self::service(ConnectionRepository::class);
    }
}
