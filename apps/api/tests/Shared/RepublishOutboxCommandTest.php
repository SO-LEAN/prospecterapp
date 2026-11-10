<?php

namespace Tests\App\Shared;

use App\Shared\Messaging\OutboxDelivery;
use App\Shared\Messaging\TransportedEvent;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\InMemoryEventBridge;

/**
 * The items of outbox still in the tables can be published again, those of the commits since a moment: harmless,
 * the reactions being idempotent.
 */
class RepublishOutboxCommandTest extends ApiTestCase
{
    public function testTheOutboxSinceAMomentIsPublishedAgain(): void
    {
        $this->publishThroughTheOutbox('prospecter.accounts', 'prospecter.personal-data', 'prospecter.prospecting.internal');

        $this->assertSame(Command::SUCCESS, $this->republish(new \DateTimeImmutable('-1 hour')));

        $this->assertEqualsCanonicalizing(['prospecter.accounts', 'prospecter.personal-data', 'prospecter.prospecting.internal'], array_column(array_merge(...$this->bus()->puts), 'Source'));
    }

    public function testTheOutboxOfTheCommitsBeforeTheMomentIsLeftAlone(): void
    {
        $this->publishThroughTheOutbox('prospecter.accounts');

        $this->republish(new \DateTimeImmutable('+1 hour'));

        $this->assertSame([], $this->bus()->puts);
    }

    private function publishThroughTheOutbox(string ...$sources): void
    {
        foreach ($sources as $source) {
            self::getContainer()->get(OutboxDelivery::class)->deliver(new TransportedEvent($source, 'Happened.v1', self::newIdentity(), '2026-11-10T09:00:00.000000Z', self::newIdentity(), null, 0, []));
        }
    }

    private function republish(\DateTimeImmutable $since): int
    {
        $tester = new CommandTester(new Application(self::$kernel)->find('app:outbox:republish'));

        return $tester->execute(['--since' => $since->format(\DATE_ATOM)]);
    }

    private function bus(): InMemoryEventBridge
    {
        return self::getContainer()->get(InMemoryEventBridge::class);
    }
}
