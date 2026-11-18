<?php

namespace Tests\App\Connectors;

use App\Shared\ScheduledTasksFunction;
use Bref\Context\Context;
use Prospecter\Core\Connectors\Application\Port\MailImportsRetriever;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\MailboxNotifications;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;

/**
 * The watch of each mailbox whose import is active renewed through the topic of its account (ADR 0154), by
 * `app:gmail:renew-watches`, what the daily schedule of an instance runs; the clock of the tests says it is
 * 2026-10-15 09:00 UTC.
 */
class RenewGmailWatchesTest extends ApiTestCase
{
    private const string TOPIC = 'projects/solean/topics/gmail';

    public function testTheConsoleRenewsTheWatchOfEachImportActive(): void
    {
        $this->aClientNotifyingTheMailboxes();
        $this->aMemberImportingTheMail('prospector-user');

        $this->assertStringContainsString('1 watch(es) of Gmail renewed, 0 failed', $this->renew());

        $this->assertSame([self::TOPIC], self::getContainer()->get(GoogleDouble::class)->watches());
    }

    public function testAWatchGmailFailsToRenewLeavesTheOthersRenewedAndItsImportActive(): void
    {
        $this->aClientNotifyingTheMailboxes();
        $this->aMemberImportingTheMail('prospector-user');
        $this->aMemberImportingTheMail('assistant-user');
        self::getContainer()->get(GoogleDouble::class)->failsTheNextWatch();

        $this->assertStringContainsString('1 watch(es) of Gmail renewed, 1 failed', $this->renew());

        $this->assertSame([self::TOPIC], self::getContainer()->get(GoogleDouble::class)->watches());
        $this->assertCount(2, self::getContainer()->get(MailImportsRetriever::class)->active(), 'A renewal failing keeps its import active, to renew the next day');
    }

    public function testTheFunctionOfTheScheduleRenewsThemToo(): void
    {
        $this->aClientNotifyingTheMailboxes();
        $this->aMemberImportingTheMail('prospector-user');

        $answer = self::getContainer()->get(ScheduledTasksFunction::class)->handle(['task' => 'renew-gmail-watches'], new Context('request', 0, 'function', 'trace'));

        $this->assertSame(['renewed' => 1, 'failed' => 0], $answer);
        $this->assertSame([self::TOPIC], self::getContainer()->get(GoogleDouble::class)->watches());
    }

    public function testATaskTheFunctionDoesNotKnowIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        self::getContainer()->get(ScheduledTasksFunction::class)->handle(['task' => 'unknown'], new Context('request', 0, 'function', 'trace'));
    }

    private function renew(): string
    {
        $tester = new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('app:gmail:renew-watches'));
        $tester->execute([]);

        return $tester->getDisplay();
    }

    private function aClientNotifyingTheMailboxes(): void
    {
        $at = new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS);
        $client = ProviderClient::configure($this->accountOfTheFixtures(), self::credentials(), new MemberId(self::newIdentity()), $at);
        $client->notifyMailboxesThrough(new MailboxNotifications(self::TOPIC, new Email('push@solean.iam.gserviceaccount.com')), new MemberId(self::newIdentity()), $at);
        self::getContainer()->get(ProviderClientRepository::class)->save($client);
    }

    private function aMemberImportingTheMail(string $member): void
    {
        $at = new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS);
        $grant = new Grant(new Email('michel@exemple.fr'), new RefreshToken('a-refresh-token'), Scope::Calendar, Scope::Mailbox);
        $connection = Connection::establish(new MemberId($this->memberIdOf($member)), $this->accountOfTheFixtures(), self::credentials(), $grant, $at);
        $connection->importMail(self::credentials(), new MailboxNotifications(self::TOPIC, new Email('push@solean.iam.gserviceaccount.com')), self::aMailbox(), $at);
        self::getContainer()->get(ConnectionRepository::class)->save($connection);
    }

    private static function credentials(): ClientCredentials
    {
        return new ClientCredentials(Provider::Google, 'a-client', 'a-secret');
    }

    private static function aMailbox(): ProviderMailbox
    {
        return new readonly class implements ProviderMailbox {
            public function watch(ClientCredentials $client, RefreshToken $refreshToken, string $topic): MailboxWatch
            {
                return new MailboxWatch('1000', new \DateTimeImmutable('2026-10-22 09:00 UTC'));
            }

            public function stop(ClientCredentials $client, RefreshToken $refreshToken): void
            {
            }
        };
    }
}
