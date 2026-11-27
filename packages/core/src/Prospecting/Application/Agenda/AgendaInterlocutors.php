<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationOfContactRetriever;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads who an agenda's exchanges are with, as Prospecting knows them (ADR 0217). Each organization met with no contact
 * is read alone: a period holds few of them, because most exchanges are with a contact. The contacts' organizations
 * come in one batch.
 */
final readonly class AgendaInterlocutors
{
    public function __construct(
        private OrganizationSheetRetriever $organizationSheetRetriever,
        private OrganizationOfContactRetriever $organizationOfContactRetriever,
    ) {
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     */
    public function of(Prospector $reader, array $exchanges): Interlocutors
    {
        $contacts = self::contactsOf($exchanges);

        return new Interlocutors(
            $this->organizationsMetAlone($exchanges),
            [] === $contacts ? [] : $this->organizationOfContactRetriever->ofContacts(array_map(static fn (string $id): ContactId => new ContactId($id), $contacts), $reader->accountId),
        );
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return list<string> the persons the exchanges are with
     */
    private static function contactsOf(array $exchanges): array
    {
        return array_values(array_filter(array_map(static fn (ExchangeInTimeline $exchange): ?string => $exchange->personId, $exchanges)));
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return array<string, OrganizationSheet> by id, without those no longer known
     */
    private function organizationsMetAlone(array $exchanges): array
    {
        $alone = array_filter($exchanges, static fn (ExchangeInTimeline $exchange): bool => null === $exchange->personId);
        $ids = array_unique(array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->organizationIds[0], $alone));

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
