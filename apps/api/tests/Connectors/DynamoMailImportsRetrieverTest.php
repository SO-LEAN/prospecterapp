<?php

declare(strict_types=1);

namespace Tests\App\Connectors;

use App\Connectors\Persistence\DynamoMailImportsRetriever;
use Prospecter\Core\Connectors\Application\Port\MailImportsRetriever;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\MailboxNotifications;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\EmptyMailbox;

/**
 * The imports of the e-mails active in every account, listed under `GSI1` for their daily renewal (ADR 0154).
 */
class DynamoMailImportsRetrieverTest extends ApiTestCase
{
    private const string AT = '2026-10-15 09:00 UTC';

    public function testTheMembersWhoseImportIsActiveAreListed(): void
    {
        $importing = self::aConnection();
        $importing->importMail(self::client(), self::notifications(), self::aMailbox(), new \DateTimeImmutable(self::AT));
        $stopped = self::aConnection();
        $stopped->importMail(self::client(), self::notifications(), self::aMailbox(), new \DateTimeImmutable(self::AT));
        $stopped->stopMailImport(null, self::aMailbox(), new \DateTimeImmutable(self::AT));
        foreach ([$importing, $stopped, self::aConnection()] as $connection) {
            self::service(ConnectionRepository::class)->save($connection);
        }

        $this->assertSame([$importing->member()->value], $this->retriever()->active());
    }

    private function retriever(): MailImportsRetriever
    {
        return new DynamoMailImportsRetriever(self::tableOf('connectors'));
    }

    private static function aConnection(): Connection
    {
        $grant = new Grant(new Email('michel@exemple.fr'), new RefreshToken('a-refresh-token'), Scope::Calendar, Scope::Mailbox);

        return Connection::establish(new Gesture(new AccountId(self::newIdentity()), new MemberId(self::newIdentity()), new \DateTimeImmutable(self::AT)), self::client(), $grant);
    }

    private static function client(): ClientCredentials
    {
        return new ClientCredentials(Provider::Google, 'a-client', 'a-secret');
    }

    private static function notifications(): MailboxNotifications
    {
        return new MailboxNotifications('projects/solean/topics/gmail', new Email('push@solean.iam.gserviceaccount.com'));
    }

    private static function aMailbox(): ProviderMailbox
    {
        return new EmptyMailbox();
    }
}
