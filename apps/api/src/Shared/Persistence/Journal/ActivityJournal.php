<?php

namespace App\Shared\Persistence\Journal;

use App\Shared\Identities;
use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Keeps every domain event published on the event bus (ADR 0043): Messenger hands it any subclass of
 * DomainEvent. An event goes to the table of its context, where its subject lives (ADR 0038, 0048), as the item
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
        return match (self::contextOf($event)) {
            'Prospecting' => $this->prospectingTable,
            'PersonalData' => $this->personalDataTable,
            'Accounts' => $this->accountsTable,
            default => throw new \LogicException(sprintf('%s happens in no known context', $event::class)),
        };
    }

    /**
     * The events of a context live in its namespace, `Prospecter\Core\<Context>\Domain\Event`.
     */
    private static function contextOf(DomainEvent $event): string
    {
        return explode('\\', $event::class)[2] ?? '';
    }

    private static function keyOf(string $subject): string
    {
        return 'JOURNAL#'.$subject;
    }
}
