<?php

namespace App\Prospecting;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\Prospecting\Domain\KnownContacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer: Prospecting asks whether a person is one the account prospects, PersonalData keeps
 * the persons (ADR 0041). The only class of Prospecting that sees PersonalData.
 */
final readonly class PersonalDataKnownContacts implements KnownContacts
{
    public function __construct(private PersonRepository $persons)
    {
    }

    public function isAContactOf(PersonId $person, AccountId $accountId): bool
    {
        try {
            $known = $this->persons->get($person);
        } catch (UnknownPerson) {
            return false;
        }

        return $known->accountId()->equals($accountId) && Purpose::Prospecting === $known->purpose();
    }
}
