<?php

declare(strict_types=1);

namespace App\Shared\Command;

use App\Shared\Messaging\IntegrationEventBus;
use App\Shared\Persistence\OutboxItem;
use App\Shared\Persistence\OutboxItems;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Publishes on the bus again the outbox items that the tables still keep, for the commits since a given moment. Use it
 * when the relay could not publish them: the relay's failures are kept for two weeks, and the items for one. It is
 * harmless, because the reactions are idempotent.
 */
#[AsCommand(name: 'app:outbox:republish', description: 'Republish on the bus the outbox events of the commits made since a given time')]
final readonly class RepublishOutboxCommand
{
    public function __construct(private OutboxItems $outbox, private IntegrationEventBus $bus)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'The time of the first commit to publish again, such as 2026-11-08T10:00:00Z')] ?string $since = null): int
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
