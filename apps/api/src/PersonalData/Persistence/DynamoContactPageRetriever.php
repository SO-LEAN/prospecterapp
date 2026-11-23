<?php

declare(strict_types=1);

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
use Prospecter\Core\SharedKernel\Domain\Search\SearchKey;

/**
 * Reads an account's contacts as cards (`PersonCards`) in the items that `DynamoPersonRepository` writes. It does not
 * restore them (ADR 0088). It lists them by account and purpose in `GSI1`, and finds them by the search indexes
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
            ? $this->personalDataTable->query(PersonalDataKeys::contactsOf($reader->accountId->value), Table::BY_GSI1, $after, $max + 1)
            : $this->inTheOrderOf($this->found($reader, $search, null === $after ? null : PersonalDataKeys::person($after), $max + 1));
        $slice = Documents::slice(array_map(static fn (array $item): PersonCard => PersonCards::of($item['person'], $reader), $items), $max, static fn (PersonCard $contact): string => $contact->id);

        return new ContactPage($slice->content, $slice->next, array_map(static fn (ContactsIntent $intent): string => $intent->name, $reader->contactsIntents()));
    }

    /**
     * @return list<string> the persons' partition keys
     */
    private function found(Reader $reader, SearchKey $search, ?string $before, int $max): array
    {
        $scope = PersonalDataKeys::scopeOf($reader->accountId, Purpose::Prospecting);

        return $reader->searchesTheEmails()
            ? SearchIndexes::found($this->personalDataTable, $scope, $search, $before, $max)
            : SearchIndexes::foundByName($this->personalDataTable, $scope, $search, $before, $max);
    }

    /**
     * @param list<string> $keys the persons' partition keys
     *
     * @return list<array<string, mixed>>
     */
    private function inTheOrderOf(array $keys): array
    {
        $byKey = array_column($this->personalDataTable->items(array_map(static fn (string $key): array => [$key, PersonalDataKeys::PERSON], $keys)), null, 'PK');

        return array_values(array_filter(array_map(static fn (string $key): ?array => $byKey[$key] ?? null, $keys)));
    }
}
