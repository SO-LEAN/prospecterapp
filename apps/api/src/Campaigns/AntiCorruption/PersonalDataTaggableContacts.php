<?php

declare(strict_types=1);

namespace App\Campaigns\AntiCorruption;

use Prospecter\Core\Campaigns\Domain\Port\TaggableContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\KeptContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContactsService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer (ADR 0045, 0089): Campaigns tags the persons PersonalData says
 * are contacts of an account, asked about a set.
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
