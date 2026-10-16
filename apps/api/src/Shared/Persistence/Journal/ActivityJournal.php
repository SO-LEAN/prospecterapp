<?php

namespace App\Shared\Persistence\Journal;

use App\Shared\Identities;
use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\SharedKernel\Domain\Event\AccountEvent;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;
use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Keeps every domain event published on the event bus (ADR 0043): Messenger hands it any subclass of
 * DomainEvent. An event goes to the table of the context of its subject (ADR 0038, 0048), as the item
 * `JOURNAL#<subject>` / `<moment>#<entry>`: what happened, who did it and when, identities only, never
 * personal data, so that the journal may be kept after an erasure.
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class ActivityJournal
{
    public function __construct(
        private Identities $identities,
        private Table $accountsTable,
        private Table $personalDataTable,
        private Table $prospectingTable,
    ) {
    }

    public function __invoke(DomainEvent $event): void
    {
        $moment = Documents::moment($event->occurredAt);

        $this->journalOf($event)->batch([[
            'PK' => self::keyOf($event->subject()->value),
            'SK' => $moment.'#'.$this->identities->next(),
            'event' => new \ReflectionClass($event)->getShortName(),
            'accountId' => $event->accountId->value,
            'memberId' => $event->by->value,
            'subjectId' => $event->subject()->value,
            'occurredAt' => $moment,
        ]]);
    }

    /**
     * What happened to a subject, oldest first.
     *
     * @return list<array{event: string, accountId: string, memberId: string, subjectId: string, occurredAt: string}>
     */
    public function of(Table $journal, string $subject): array
    {
        return array_reverse($journal->query(self::keyOf($subject)));
    }

    private function journalOf(DomainEvent $event): Table
    {
        return match (true) {
            $event instanceof OrganizationEvent => $this->prospectingTable,
            $event instanceof PersonEvent => $this->personalDataTable,
            $event instanceof AccountEvent => $this->accountsTable,
            default => throw new \LogicException(sprintf('%s happens to no known kind of aggregate', $event::class)),
        };
    }

    private static function keyOf(string $subject): string
    {
        return 'JOURNAL#'.$subject;
    }
}
