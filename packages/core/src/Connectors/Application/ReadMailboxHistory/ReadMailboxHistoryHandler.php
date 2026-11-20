<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ReadMailboxHistory;

use Prospecter\Core\Connectors\Domain\Event\MailboxToRead;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * The history of the mailbox read from where it was read last, once it notified a change or its watch was renewed,
 * each message added to import on its own (ADR 0154), as long as the member manages the personal data. A member no
 * longer connected, or whose account lost its client, has nothing read.
 */
final readonly class ReadMailboxHistoryHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ConnectionRepository $connections,
        private ProviderClientRepository $clients,
        private ProviderMailbox $mailbox,
        private Members $members,
        private Events $events,
    ) {
    }

    public function __invoke(MailboxToRead $event): void
    {
        $connection = $this->connections->find($event->subject(), Provider::Google);
        $client = $this->clients->find($event->accountId, Provider::Google);
        if (null === $connection || null === $client) {
            return;
        }

        $connection->readMailboxHistory($this->members->get($event->subject()), $client->credentials(), $this->mailbox, $event->occurredAt);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }
}
