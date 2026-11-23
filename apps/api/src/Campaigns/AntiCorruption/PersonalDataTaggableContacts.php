<?php

declare(strict_types=1);

namespace App\Campaigns\AntiCorruption;

use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Port\TaggableContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\KeptContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContactsService;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Anticorruption layer (ADR 0045, 0089). Campaigns tags the people that PersonalData says are contacts of an account.
 * It asks about a set.
 */
final readonly class PersonalDataTaggableContacts implements TaggableContacts
{
    public function __construct(private WhichAreContactsService $whichAreContactsService)
    {
    }

    /**
     * @param list<ContactId> $persons
     */
    public function contactsAmong(array $persons, AccountId $accountId): array
    {
        $kept = new KeptContacts();
        ($this->whichAreContactsService)(new WhichAreContacts(array_map(static fn (ContactId $person): string => $person->value, $persons), $accountId->value), $kept);

        return array_values(array_filter($persons, static fn (ContactId $person): bool => in_array($person->value, $kept->personIds, true)));
    }
}
