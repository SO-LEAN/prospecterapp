<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\KeptContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContactsService;
use Prospecter\Core\Prospecting\Domain\Port\KnownContacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089): Prospecting knows the persons it prospects, PersonalData
 * tells which persons are contacts of an account.
 */
final readonly class PersonalDataKnownContacts implements KnownContacts
{
    public function __construct(private WhichAreContactsService $whichAreContactsService)
    {
    }

    public function isAContactOf(PersonId $person, AccountId $accountId): bool
    {
        $kept = new KeptContacts();
        ($this->whichAreContactsService)(new WhichAreContacts([$person->value], $accountId->value), $kept);

        return in_array($person->value, $kept->personIds, true);
    }
}
