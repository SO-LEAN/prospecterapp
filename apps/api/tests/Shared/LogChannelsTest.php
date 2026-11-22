<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Connectors\AntiCorruption\PersonalDataAndProspectingCorrespondents;
use App\Connectors\Google\GoogleMailbox;
use App\Connectors\MailWatchesRenewer;
use App\Shared\Messaging\IntegrationEventBus;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * What an instance logs at info, out of what it logs only before an error (ADR 0167): the import of the e-mails of
 * Gmail in the channel `mailbox`, the relay and the worker in the channel `messaging`.
 */
class LogChannelsTest extends KernelTestCase
{
    /**
     * @param class-string $service
     */
    #[DataProvider('channels')]
    public function testAServiceLogsInTheChannelOfWhatItDoes(string $service, string $channel): void
    {
        $logger = new \ReflectionProperty($service, 'logger')->getValue(self::getContainer()->get($service));

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($channel, $logger->getName());
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function channels(): iterable
    {
        yield 'the reading of a mailbox' => [GoogleMailbox::class, 'mailbox'];
        yield 'the senders recognized' => [PersonalDataAndProspectingCorrespondents::class, 'mailbox'];
        yield 'the renewal of the watches' => [MailWatchesRenewer::class, 'mailbox'];
        yield 'the bus the relay and the worker publish on' => [IntegrationEventBus::class, 'messaging'];
    }
}
