<?php

namespace App\Shared\Persistence\Journal;

use App\Shared\Identities;
use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\SharedKernel\Domain\Event\AccountEvent;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;
use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Keeps every domain event published on the event bus (ADR 0043): Messenger hands it any subclass of
 * DomainEvent. An event goes to the database of the context of its subject (ADR 0038).
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class ActivityJournal
{
    public function __construct(
        private Identities $identities,
        private EntityManagerInterface $accountsEntityManager,
        private EntityManagerInterface $personalDataEntityManager,
        private EntityManagerInterface $prospectingEntityManager,
    ) {
    }

    public function __invoke(DomainEvent $event): void
    {
        $journal = $this->journalOf($event);
        $journal->persist(new JournalEntry(
            $this->identities->next(),
            $event->occurredAt,
            $event->accountId->value,
            $event->by->value,
            new \ReflectionClass($event)->getShortName(),
            $event->subject()->value,
        ));
        $journal->flush();
    }

    private function journalOf(DomainEvent $event): EntityManagerInterface
    {
        return match (true) {
            $event instanceof OrganizationEvent => $this->prospectingEntityManager,
            $event instanceof PersonEvent => $this->personalDataEntityManager,
            $event instanceof AccountEvent => $this->accountsEntityManager,
            default => throw new \LogicException(sprintf('%s happens to no known kind of aggregate', $event::class)),
        };
    }
}
