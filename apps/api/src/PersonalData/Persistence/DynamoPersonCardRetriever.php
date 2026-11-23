<?php

declare(strict_types=1);

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Application\PossibleDuplicate;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Rule\PersonReading;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;

/**
 * Reads the items `PERSON#<id>` / `PERSON` that `DynamoPersonRepository` writes, as cards (`PersonCards`). It does
 * not restore the persons (ADR 0088). It finds a calling contact by the phone number index (ADR 0072).
 */
final readonly class DynamoPersonCardRetriever implements PersonCardRetriever
{
    public function __construct(private Table $personalDataTable)
    {
    }

    public function get(PersonId $id, Reader $reader): PersonCard
    {
        $item = $this->personalDataTable->item(PersonalDataKeys::person($id->value), PersonalDataKeys::PERSON);
        $document = null === $item ? null : Typed::document($item['person']);

        if (null === $document || !PersonCards::isReachableBy($reader, $document)) {
            throw UnknownPerson::identifiedBy($id);
        }

        return PersonCards::of($document, $reader, PersonReading::showsPossibleDuplicatesTo($reader) ? $this->possibleDuplicatesOf($document) : []);
    }

    /**
     * Reads, in one batch, the contacts that the person may duplicate. A contact forgotten meanwhile is left out.
     *
     * @param array<string, mixed> $document
     *
     * @return list<PossibleDuplicate>
     */
    private function possibleDuplicatesOf(array $document): array
    {
        $ids = Typed::strings($document['possibleDuplicates'] ?? []);
        $named = [];

        foreach ($this->personalDataTable->items(array_map(static fn (string $id): array => [PersonalDataKeys::person($id), PersonalDataKeys::PERSON], $ids)) as $item) {
            $duplicate = self::possibleDuplicate(Typed::document($item['person']));
            $named[$duplicate->id] = $duplicate;
        }

        return array_values(array_filter(array_map(static fn (string $id): ?PossibleDuplicate => $named[$id] ?? null, $ids)));
    }

    /**
     * @param array<string, mixed> $person
     */
    private static function possibleDuplicate(array $person): PossibleDuplicate
    {
        $name = Typed::document($person['name']);

        return new PossibleDuplicate(Typed::string($person['id']), Typed::stringOrNull($name['firstName'] ?? null), Typed::stringOrNull($name['lastName'] ?? null));
    }

    /**
     * @param list<PersonId> $ids
     */
    public function ofIds(array $ids, Reader $reader): array
    {
        return $this->reachableCardsOf(array_map(static fn (PersonId $id): string => PersonalDataKeys::person($id->value), $ids), $reader);
    }

    public function contactsWithPhoneNumber(PhoneNumber $phoneNumber, Reader $reader): array
    {
        return $this->reachableCardsOf(SearchIndexes::withPhoneNumber($this->personalDataTable, PersonalDataKeys::scopeOf($reader->accountId, Purpose::Prospecting), $phoneNumber), $reader);
    }

    /**
     * Returns the cards of the persons behind these keys that the reader reaches, in the keys' order.
     *
     * @param list<string> $keys
     *
     * @return list<PersonCard>
     */
    private function reachableCardsOf(array $keys, Reader $reader): array
    {
        $byKey = array_column($this->personalDataTable->items(array_map(static fn (string $key): array => [$key, PersonalDataKeys::PERSON], $keys)), null, 'PK');
        $cards = [];

        foreach ($keys as $key) {
            $document = $byKey[$key]['person'] ?? null;

            if (null !== $document && PersonCards::isReachableBy($reader, $document)) {
                $cards[] = PersonCards::of($document, $reader);
            }
        }

        return $cards;
    }
}
