<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Domain\Port\TaggableContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\KeptContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContactsService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089). Campaigns tags the persons that PersonalData
 * says are an account's contacts. It asks about a set of persons.
 */
final readonly class PersonalDataTaggableContacts implements TaggableContacts
{
    public function __construct(private WhichAreContactsService $whichAreContactsService)
    {
    }

    /**
     * @param list<PersonId> $persons
     */
    public function contactsAmong(array $persons, AccountId $accountId): array
    {
        $kept = new KeptContacts();
        ($this->whichAreContactsService)(new WhichAreContacts(array_map(static fn (PersonId $person): string => $person->value, $persons), $accountId->value), $kept);

        return array_values(array_filter($persons, static fn (PersonId $person): bool => in_array($person->value, $kept->personIds, true)));
    }
}
