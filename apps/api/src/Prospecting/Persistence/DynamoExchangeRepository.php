<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Revision;
use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The exchanges in the table of Prospecting (ADR 0048, 0074, 0076):
 * - `EXCHANGE#<id>` / `EXCHANGE`: the exchange, listed in `GSI1` in the timeline of its contact
 *   (`TIMELINE#<account>#<person>`), under its position: the moment it happened and its id, the cursor of a slice;
 *   an exchange with the organization itself is in the timeline of no contact;
 * - `EXCHANGES#<organization>` / `EXCHANGE#<id>`: the exchanges kept for an organization, written with it, listed
 *   in `GSI1` in the timeline of the organization (`ORGANIZATION_TIMELINE#<organization>`) under the same position,
 *   and, while the exchange is to make conform, in `GSI2` among those of the organization
 *   (`TO_MAKE_CONFORM#<organization>`, ADR 0079), counted there;
 * - `AGENDA#<member>` / `EXCHANGE#<id>`: while it is planned, the exchange in the agenda of the member who planned
 *   it, listed in `GSI1` (`AGENDA#<member>`) under the same position.
 * The exchange is listed in `GSI2` among those of its account (`ACCOUNT_EXCHANGES#<account>`, its id), and
 * carries its `revision`, incremented at each save: the revision of its facts, which the aggregate does not know.
 */
final readonly class DynamoExchangeRepository implements ExchangeRepository
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function get(ExchangeId $id): Exchange
    {
        $item = $this->prospectingTable->item(ExchangeKeys::exchange($id->value), ExchangeKeys::EXCHANGE) ?? throw UnknownExchange::identifiedBy($id);

        return ExchangeDocument::exchange($item['exchange']);
    }

    public function save(Exchange $exchange): void
    {
        $document = ExchangeDocument::of($exchange);
        $stored = $this->prospectingTable->item(ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE);

        $this->prospectingTable->transact([
            $this->prospectingTable->putting($this->itemOf($document, Revision::after($stored))),
            ...$this->linksToItsOrganizations($document, $exchange->isToMakeConform(), $stored['exchange']['keptFor'] ?? []),
            ...$this->linkInTheAgendaOfItsHolder($document, $stored['exchange'] ?? null),
        ]);
    }

    public function remove(Exchange $exchange): void
    {
        $document = ExchangeDocument::of($exchange);
        $stored = $this->prospectingTable->item(ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE);

        $this->prospectingTable->transact([
            $this->prospectingTable->deleting(ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE),
            ...$this->linksToItsOrganizations(['keptFor' => []] + $document, false, $stored['exchange']['keptFor'] ?? []),
            ...$this->linkInTheAgendaOfItsHolder(['status' => ExchangeStatus::Done->value] + $document, $stored['exchange'] ?? null),
        ]);
    }

    public function keptFor(OrganizationId $organization): array
    {
        $links = $this->prospectingTable->query(ExchangeKeys::keptFor($organization->value));

        return $this->exchangesOf($this->prospectingTable->items(array_map(static fn (array $link): array => [$link['SK'], ExchangeKeys::EXCHANGE], $links)));
    }

    public function withContact(PersonId $contact, AccountId $accountId): array
    {
        return $this->exchangesOf($this->prospectingTable->query(ExchangeKeys::timeline($accountId->value, $contact->value), Table::BY_GSI1));
    }

    /**
     * Writes again the item of every exchange, its revision kept: what a table written before the exchanges were
     * listed by account needs once.
     */
    public function writeTheKeysAgain(): int
    {
        $items = $this->prospectingTable->scan(ExchangeKeys::EXCHANGE.'#');
        $this->prospectingTable->batch(array_map(fn (array $item): array => $this->itemOf($item['exchange'], Revision::of($item)), $items));

        return count($items);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function itemOf(array $document, int $revision): array
    {
        $inTheTimelineOfItsContact = null === $document['contact'] ? [] : [
            'GSI1PK' => ExchangeKeys::timeline($document['accountId'], $document['contact']),
            'GSI1SK' => ExchangeKeys::positionOf($document),
        ];

        return [
            'PK' => ExchangeKeys::exchange($document['id']),
            'SK' => ExchangeKeys::EXCHANGE,
            ...$inTheTimelineOfItsContact,
            'GSI2PK' => ExchangeKeys::ofAccount($document['accountId']),
            'GSI2SK' => $document['id'],
            Revision::ATTRIBUTE => $revision,
            'exchange' => $document,
        ];
    }

    /**
     * The links to write and to delete so that the organizations the exchange is kept for are those of the
     * document, each at the moment of the exchange in the timeline of its organization: a correction may move it;
     * among those to make conform of its organization while it is.
     *
     * @param array<string, mixed> $document
     * @param list<string>         $storedKeptFor
     *
     * @return list<Write>
     */
    private function linksToItsOrganizations(array $document, bool $toMakeConform, array $storedKeptFor): array
    {
        $exchange = ExchangeKeys::exchange($document['id']);
        $position = ExchangeKeys::positionOf($document);
        $link = static fn (string $organization): array => [
            'PK' => ExchangeKeys::keptFor($organization),
            'SK' => $exchange,
            'GSI1PK' => ExchangeKeys::organizationTimeline($organization),
            'GSI1SK' => $position,
            ...($toMakeConform ? ['GSI2PK' => ExchangeKeys::toMakeConform($organization), 'GSI2SK' => $position] : []),
        ];

        return [
            ...array_map(fn (string $organization): Write => $this->prospectingTable->putting($link($organization)), $document['keptFor']),
            ...array_map(fn (string $organization): Write => $this->prospectingTable->deleting(ExchangeKeys::keptFor($organization), $exchange), array_values(array_diff($storedKeptFor, $document['keptFor']))),
        ];
    }

    /**
     * While it is planned, the exchange is in the agenda of the member who planned it, at its moment; the link
     * removed once it no longer is.
     *
     * @param array<string, mixed>      $document
     * @param array<string, mixed>|null $stored
     *
     * @return list<Write>
     */
    private function linkInTheAgendaOfItsHolder(array $document, ?array $stored): array
    {
        $agenda = ExchangeKeys::agenda($document['createdBy']);
        $exchange = ExchangeKeys::exchange($document['id']);

        return match (true) {
            ExchangeStatus::Planned === ExchangeDocument::statusOf($document) => [$this->prospectingTable->putting(['PK' => $agenda, 'SK' => $exchange, 'GSI1PK' => $agenda, 'GSI1SK' => ExchangeKeys::positionOf($document)])],
            null !== $stored && ExchangeStatus::Planned === ExchangeDocument::statusOf($stored) => [$this->prospectingTable->deleting($agenda, $exchange)],
            default => [],
        };
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<Exchange>
     */
    private function exchangesOf(array $items): array
    {
        return array_map(static fn (array $item): Exchange => ExchangeDocument::exchange($item['exchange']), $items);
    }
}
