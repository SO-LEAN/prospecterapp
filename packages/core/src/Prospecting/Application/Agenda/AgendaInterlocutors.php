<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda;

use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationOfContactRetriever;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\Port\PersonDescriptionRetriever;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads who an agenda's exchanges and to-dos are with (ADR 0217). PersonalData names the persons, the contacts and the
 * to-dos' holders, in one call. Each organization met with no contact is read alone: a period holds few of them,
 * because most exchanges are with a contact. The contacts' organizations come in one batch.
 */
final readonly class AgendaInterlocutors
{
    public function __construct(
        private PersonDescriptionRetriever $personDescriptionRetriever,
        private OrganizationSheetRetriever $organizationSheetRetriever,
        private OrganizationOfContactRetriever $organizationOfContactRetriever,
    ) {
    }

    /**
     * @param list<ExchangeInTimeline>    $exchanges
     * @param list<ToDoInList>            $toDos
     * @param list<ContactToAttachInList> $contactsToAttach
     */
    public function of(Prospector $reader, array $exchanges, array $toDos = [], array $contactsToAttach = []): Interlocutors
    {
        $contactsOfExchanges = self::contactsOf($exchanges);
        $persons = array_values(array_unique([
            ...$contactsOfExchanges,
            ...array_filter(array_map(static fn (ToDoInList $toDo): ?string => $toDo->personId, $toDos)),
            ...array_map(static fn (ContactToAttachInList $contact): string => $contact->personId, $contactsToAttach),
        ]));
        $holders = array_values(array_unique(array_map(static fn (ToDoInList $toDo): string => $toDo->heldBy, $toDos)));

        return new Interlocutors(
            $this->personDescriptionRetriever->shownTo($reader->id, self::contactIds($persons), array_map(static fn (string $holder): MemberId => new MemberId($holder), $holders)),
            $this->organizationsMetAlone($exchanges),
            [] === $contactsOfExchanges ? [] : $this->organizationOfContactRetriever->ofContacts(self::contactIds($contactsOfExchanges), $reader->accountId),
        );
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return list<string> the persons the exchanges are with, once each
     */
    private static function contactsOf(array $exchanges): array
    {
        return array_values(array_unique(array_filter(array_map(static fn (ExchangeInTimeline $exchange): ?string => $exchange->personId, $exchanges))));
    }

    /**
     * @param list<string> $ids
     *
     * @return list<ContactId>
     */
    private static function contactIds(array $ids): array
    {
        return array_map(static fn (string $id): ContactId => new ContactId($id), $ids);
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return array<string, OrganizationSheet> by id, without those no longer known
     */
    private function organizationsMetAlone(array $exchanges): array
    {
        $alone = array_filter($exchanges, static fn (ExchangeInTimeline $exchange): bool => null === $exchange->personId);
        $ids = array_values(array_unique(array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->organizationIds[0], $alone)));

        return array_filter(array_combine($ids, array_map($this->organization(...), $ids)));
    }

    private function organization(string $id): ?OrganizationSheet
    {
        try {
            return $this->organizationSheetRetriever->get(new OrganizationId($id));
        } catch (UnknownOrganization) {
            return null;
        }
    }
}
