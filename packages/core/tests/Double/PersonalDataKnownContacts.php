<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\KeptContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContactsService;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Port\KnownContacts;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089). Prospecting knows the persons it prospects.
 * PersonalData tells which persons are contacts of an account.
 */
final readonly class PersonalDataKnownContacts implements KnownContacts
{
    public function __construct(private WhichAreContactsService $whichAreContactsService)
    {
    }

    public function isAContactOf(ContactId $person, AccountId $accountId): bool
    {
        return [] !== $this->whichAreContactsOf([$person], $accountId);
    }

    /**
     * @param list<ContactId> $persons
     *
     * @return list<ContactId>
     */
    public function whichAreContactsOf(array $persons, AccountId $accountId): array
    {
        if ([] === $persons) {
            return [];
        }

        $kept = new KeptContacts();
        ($this->whichAreContactsService)(new WhichAreContacts(array_map(static fn (ContactId $person): string => $person->value, $persons), $accountId->value), $kept);

        return array_values(array_filter($persons, static fn (ContactId $person): bool => in_array($person->value, $kept->personIds, true)));
    }
}
