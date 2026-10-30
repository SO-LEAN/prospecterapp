<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\ContactPageRetriever;
use Prospecter\Core\PersonalData\Application\SearchContacts\ContactPage;
use Prospecter\Core\PersonalData\Domain\Model\ContactsIntent;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\SearchKey;

/**
 * Reads the contacts of an account in the items `DynamoPersonRepository` writes, as cards (`PersonCards`),
 * without restoring them (ADR 0088): listed by account and purpose in `GSI1`, found by the indexes of the search
 * (ADR 0071).
 */
final readonly class DynamoContactPageRetriever implements ContactPageRetriever
{
    public function __construct(private Table $personalDataTable)
    {
    }

    public function sliceFor(Reader $reader, ?SearchKey $search, ?string $after, int $max): ContactPage
    {
        $items = null === $search
            ? $this->personalDataTable->query(PersonalDataKeys::listOf($reader->accountId, Purpose::Prospecting), Table::BY_GSI1, $after, $max + 1)
            : $this->inTheOrderOf(SearchIndexes::found($this->personalDataTable, PersonalDataKeys::scopeOf($reader->accountId, Purpose::Prospecting), $search, null === $after ? null : PersonalDataKeys::person($after), $max + 1));
        $slice = Documents::slice(array_map(static fn (array $item): PersonCard => PersonCards::of($item['person'], $reader), $items), $max, static fn (PersonCard $contact): string => $contact->id);

        return new ContactPage($slice->content, $slice->next, array_map(static fn (ContactsIntent $intent): string => $intent->name, $reader->contactsIntents()));
    }

    /**
     * @param list<string> $keys partition keys of persons
     *
     * @return list<array<string, mixed>>
     */
    private function inTheOrderOf(array $keys): array
    {
        $byKey = array_column($this->personalDataTable->items(array_map(static fn (string $key): array => [$key, PersonalDataKeys::PERSON], $keys)), null, 'PK');

        return array_values(array_filter(array_map(static fn (string $key): ?array => $byKey[$key] ?? null, $keys)));
    }
}
