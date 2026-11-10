<?php

namespace App\Shared\Command;

use App\Shared\Messaging\IntegrationEventBus;
use App\Shared\Persistence\OutboxItem;
use App\Shared\Persistence\OutboxItems;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Publishes again on the bus the items of outbox the tables still keep, those of the commits since a moment: when
 * the relay could not publish them (its failures are kept two weeks, the items one). Harmless: the reactions are
 * idempotent.
 */
#[AsCommand(name: 'app:outbox:republish', description: 'Publish again on the bus the events of the outbox of the commits since a moment')]
final readonly class RepublishOutboxCommand
{
    public function __construct(private OutboxItems $outbox, private IntegrationEventBus $bus)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'The moment of the first commit to publish again, such as 2026-11-08T10:00:00Z')] ?string $since = null): int
    {
        if (null === $since) {
            $io->error('Name the moment of the first commit to publish again: --since=2026-11-08T10:00:00Z');

            return Command::INVALID;
        }

        $items = $this->outbox->since(new \DateTimeImmutable($since));
        $this->bus->publish(...array_merge(...array_map(static fn (OutboxItem $item): array => $item->events, $items)));
        $io->success(sprintf('Published again the events of %d commits since %s.', count($items), $since));

        return Command::SUCCESS;
    }
}
