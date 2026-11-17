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
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089): Campaigns tags the persons PersonalData says
 * are contacts of an account, asked about a set of one.
 */
final readonly class PersonalDataTaggableContacts implements TaggableContacts
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
