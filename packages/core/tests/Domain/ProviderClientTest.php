<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Event\ClientConfigured;
use Prospecter\Core\Connectors\Domain\Event\ClientRemoved;
use Prospecter\Core\Connectors\Domain\Exception\IncompleteClient;
use Prospecter\Core\Connectors\Domain\Exception\NotTheAdministrator;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxNotifications;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Each gesture on an account's client records what happened. A client has its id and its secret. Only an
 * administrator of the account configures it.
 */
final class ProviderClientTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string ADMINISTRATOR = '0190a1c0-0000-7000-8000-0000000000aa';
    private const string AT = '2026-11-15 10:00';

    public function testAClientConfiguredRecordsIt(): void
    {
        $client = $this->configure();

        $this->assertEquals([$this->happened(ClientConfigured::class)], $client->releaseEvents());
    }

    public function testAClientReplacedRecordsIt(): void
    {
        $client = $this->configure();
        $client->releaseEvents();

        $client->replaceBy(new ClientCredentials(Provider::Google, 'another-client', 'its-secret'), new MemberId(self::ADMINISTRATOR), new \DateTimeImmutable(self::AT));

        $this->assertEquals([$this->happened(ClientConfigured::class)], $client->releaseEvents());
        $this->assertSame('another-client', $client->credentials()->id);
    }

    public function testTheTopicOfTheMailboxesNamedRecordsIt(): void
    {
        $client = $this->configure();
        $client->releaseEvents();

        $client->notifyMailboxesThrough(new MailboxNotifications('projects/solean/topics/gmail', new Email('push@solean.iam.gserviceaccount.com')), new MemberId(self::ADMINISTRATOR), new \DateTimeImmutable(self::AT));

        $this->assertEquals([$this->happened(ClientConfigured::class)], $client->releaseEvents());
    }

    public function testTheTopicOfTheMailboxesRemovedRecordsIt(): void
    {
        $client = $this->configure();
        $client->releaseEvents();

        $client->stopMailboxNotificationsBy(new MemberId(self::ADMINISTRATOR), new \DateTimeImmutable(self::AT));

        $this->assertEquals([$this->happened(ClientConfigured::class)], $client->releaseEvents());
    }

    public function testAClientRemovedRecordsIt(): void
    {
        $client = $this->configure();
        $client->releaseEvents();

        $client->removeBy(new MemberId(self::ADMINISTRATOR), new \DateTimeImmutable(self::AT));

        $this->assertEquals([$this->happened(ClientRemoved::class)], $client->releaseEvents());
    }

    public function testAClientNeedsItsId(): void
    {
        $this->expectExceptionObject(IncompleteClient::withoutItsId());

        new ClientCredentials(Provider::Google, '', 'its-secret');
    }

    public function testAClientNeedsItsSecret(): void
    {
        $this->expectExceptionObject(IncompleteClient::withoutItsSecret());

        new ClientCredentials(Provider::Google, 'the-client', '');
    }

    public function testAnAdministratorAdministersTheirAccount(): void
    {
        $administrator = new Member(new MemberId(self::ADMINISTRATOR), new AccountId(self::ACCOUNT), administers: true, reminderMinutesBefore: null, managesPersonalData: false);

        $this->assertEquals(new AccountId(self::ACCOUNT), $administrator->administeredAccount());
    }

    public function testAMemberWhoIsNoAdministratorAdministersNothing(): void
    {
        $prospector = new Member(new MemberId(self::ADMINISTRATOR), new AccountId(self::ACCOUNT), administers: false, reminderMinutesBefore: null, managesPersonalData: false);

        $this->expectExceptionObject(NotTheAdministrator::ofTheClients());

        $prospector->administeredAccount();
    }

    private function configure(): ProviderClient
    {
        return ProviderClient::configure(new AccountId(self::ACCOUNT), new ClientCredentials(Provider::Google, 'the-client', 'its-secret'), new MemberId(self::ADMINISTRATOR), new \DateTimeImmutable(self::AT));
    }

    /**
     * @param class-string<ClientConfigured|ClientRemoved> $event
     */
    private function happened(string $event): ClientConfigured|ClientRemoved
    {
        return new $event(new AccountId(self::ACCOUNT), new MemberId(self::ADMINISTRATOR), new \DateTimeImmutable(self::AT));
    }
}
