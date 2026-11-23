<?php

declare(strict_types=1);

namespace Tests\App\Connectors;

use App\Connectors\Persistence\ConnectorsKeys;
use App\Shared\Persistence\Tables;
use App\Shared\Typed;
use Aws\Ssm\Exception\SsmException;
use Aws\Ssm\SsmClient;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\Port\AccountClientsRetriever;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxNotifications;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\App\Base\ApiTestCase;

/**
 * The client of an account (ADR 0125): its id in the table of Connectors, its secret a `SecureString` of SSM, the
 * version the item names read, so that a commit failing after the secret was put keeps the client as it was.
 */
class DynamoProviderClientRepositoryTest extends ApiTestCase
{
    private const string AT = '2026-11-15 10:00';

    public function testTheSecretIsKeptInSsmNotInTheTable(): void
    {
        $account = $this->anAccount();

        $this->clients()->save($this->clientOf($account, 'the-client', 'its-secret'));

        $item = self::storedItem(self::tableOf('connectors'), ConnectorsKeys::account($account->value), ConnectorsKeys::client(Provider::Google->value));
        $this->assertSame('the-client', Typed::document($item['client'])['clientId']);
        $this->assertStringNotContainsString('its-secret', (string) json_encode($item));
        $parameter = Typed::document($this->ssm()->getParameter(['Name' => $this->secretOf($account), 'WithDecryption' => true])['Parameter']);
        $this->assertSame(['SecureString', 'its-secret'], [$parameter['Type'], $parameter['Value']]);
        $this->assertEquals(new ClientCredentials(Provider::Google, 'the-client', 'its-secret'), $this->clients()->get($account, Provider::Google)->credentials());
    }

    public function testAClientReplacedIsReadWithItsNewSecret(): void
    {
        $account = $this->anAccount();
        $client = $this->clientOf($account, 'the-client', 'its-secret');
        $this->clients()->save($client);

        $client->replaceBy(new ClientCredentials(Provider::Google, 'another-client', 'another-secret'), new MemberId(self::newIdentity()), new \DateTimeImmutable(self::AT));
        $this->clients()->save($client);

        $this->assertEquals(new ClientCredentials(Provider::Google, 'another-client', 'another-secret'), $this->clients()->get($account, Provider::Google)->credentials());
    }

    public function testACommitDiscardedAfterTheSecretWasPutKeepsTheFormerClient(): void
    {
        $account = $this->anAccount();
        $client = $this->clientOf($account, 'the-client', 'its-secret');
        $this->clients()->save($client);

        $this->unitOfWork()->begin();
        $client->replaceBy(new ClientCredentials(Provider::Google, 'another-client', 'another-secret'), new MemberId(self::newIdentity()), new \DateTimeImmutable(self::AT));
        $this->clients()->save($client);
        $this->unitOfWork()->discard();

        $this->assertEquals(new ClientCredentials(Provider::Google, 'the-client', 'its-secret'), $this->clients()->get($account, Provider::Google)->credentials());
    }

    public function testAClientRemovedForgetsItsSecret(): void
    {
        $account = $this->anAccount();
        $client = $this->clientOf($account, 'the-client', 'its-secret');
        $this->clients()->save($client);

        $this->clients()->remove($client);

        $this->assertNull($this->clients()->find($account, Provider::Google));
        $this->assertFalse($this->hasItsSecret($account));
    }

    public function testAClientWhoseSecretIsGoneIsNoClient(): void
    {
        $account = $this->anAccount();
        $this->clients()->save($this->clientOf($account, 'the-client', 'its-secret'));

        $this->ssm()->deleteParameter(['Name' => $this->secretOf($account)]);

        $this->assertNull($this->clients()->find($account, Provider::Google));
    }

    public function testRecreatingTheTablesForgetsTheSecretsOfTheAccounts(): void
    {
        $account = $this->anAccount();
        $this->clients()->save($this->clientOf($account, 'the-client', 'its-secret'));

        self::service(Tables::class)->recreate();

        $this->assertFalse($this->hasItsSecret($account));
    }

    public function testAClientKeepsTheTopicItsMailboxesNotifyThrough(): void
    {
        $account = $this->anAccount();
        $client = $this->clientOf($account, 'the-client', 'its-secret');
        $notifications = new MailboxNotifications('projects/solean/topics/gmail', new Email('push@solean.iam.gserviceaccount.com'));
        $client->notifyMailboxesThrough($notifications, new MemberId(self::newIdentity()), new \DateTimeImmutable(self::AT));

        $this->clients()->save($client);

        $this->assertEquals($notifications, $this->clients()->get($account, Provider::Google)->mailboxNotifications());
        $this->assertEquals([new AccountClient('google', 'the-client', 'projects/solean/topics/gmail', 'push@solean.iam.gserviceaccount.com')], self::service(AccountClientsRetriever::class)->of($account));
    }

    private function anAccount(): AccountId
    {
        return new AccountId(self::newIdentity());
    }

    private function clientOf(AccountId $account, string $id, string $secret): ProviderClient
    {
        return ProviderClient::configure($account, new ClientCredentials(Provider::Google, $id, $secret), new MemberId(self::newIdentity()), new \DateTimeImmutable(self::AT));
    }

    private function secretOf(AccountId $account): string
    {
        return sprintf('%s/%s/google-client-secret', Typed::string($_SERVER['ACCOUNT_SECRETS_PATH']), $account->value);
    }

    private function hasItsSecret(AccountId $account): bool
    {
        try {
            $this->ssm()->getParameter(['Name' => $this->secretOf($account)]);

            return true;
        } catch (SsmException $missing) {
            return 'ParameterNotFound' !== $missing->getAwsErrorCode();
        }
    }

    private function clients(): ProviderClientRepository
    {
        return self::service(ProviderClientRepository::class);
    }

    private function unitOfWork(): UnitOfWork
    {
        return self::service(UnitOfWork::class);
    }

    private function ssm(): SsmClient
    {
        return self::service(SsmClient::class);
    }
}
