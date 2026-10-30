<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Reads the items `PERSON#<id>` / `PERSON` that `DynamoPersonRepository` writes as cards (`PersonCards`),
 * without restoring the persons (ADR 0088); a calling contact by the index of the phone numbers (ADR 0072).
 */
final readonly class DynamoPersonCardRetriever implements PersonCardRetriever
{
    public function __construct(private Table $personalDataTable)
    {
    }

    public function get(PersonId $id, Reader $reader): PersonCard
    {
        $document = $this->personalDataTable->item(PersonalDataKeys::person($id->value), PersonalDataKeys::PERSON)['person'] ?? null;

        if (null === $document || !PersonCards::isReachableBy($reader, $document)) {
            throw UnknownPerson::identifiedBy($id);
        }

        return PersonCards::of($document, $reader);
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
     * The cards of the persons of these keys the reader reaches, in the order of the keys.
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
