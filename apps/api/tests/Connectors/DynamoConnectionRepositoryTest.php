<?php

namespace Tests\App\Connectors;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\MailboxNotifications;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;

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

    /**
     * @return array<string, mixed>
     */
    private static function importOf(Connection $connection): array
    {
        return array_intersect_key(Hydrator::stateOf($connection), array_flip(['mailImport', 'historyId', 'watchExpiresAt', 'modifiedAt']));
    }

    private static function aMailboxStandingAt(string $historyId): ProviderMailbox
    {
        return new readonly class($historyId) implements ProviderMailbox {
            public function __construct(private string $historyId)
            {
            }

            public function watch(ClientCredentials $client, RefreshToken $refreshToken, string $topic): MailboxWatch
            {
                return new MailboxWatch($this->historyId, new \DateTimeImmutable('2026-10-22 09:01 UTC'));
            }

            public function stop(ClientCredentials $client, RefreshToken $refreshToken): void
            {
            }
        };
    }

    private static function aConnection(?MemberId $member = null, RefreshToken $token = new RefreshToken('a-refresh-token'), string $at = '2026-10-15 09:00 UTC'): Connection
    {
        $grant = new Grant(new Email('michel@exemple.fr'), $token, Scope::Calendar, Scope::Mailbox);

        return Connection::establish($member ?? new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), new ClientCredentials(Provider::Google, 'a-client', 'a-secret'), $grant, new \DateTimeImmutable($at));
    }

    private function connections(): ConnectionRepository
    {
        return self::getContainer()->get(ConnectionRepository::class);
    }
}
