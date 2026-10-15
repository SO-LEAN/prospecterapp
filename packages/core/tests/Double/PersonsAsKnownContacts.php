<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\Prospecting\Domain\KnownContacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The contacts known to Prospecting read from the persons of PersonalData, like the anticorruption layer of
 * the API.
 */
final readonly class PersonsAsKnownContacts implements KnownContacts
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
