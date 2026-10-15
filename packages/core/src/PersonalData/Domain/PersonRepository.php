<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\PersonId;

interface PersonRepository
{
    /**
     * @throws UnknownPerson
     */
    public function get(PersonId $id): Person;

    /**
     * The persons that exist among these ids, in no particular order.
     *
     * @param list<PersonId> $ids
     *
     * @return list<Person>
     */
    public function ofIds(array $ids): array;

    /**
     * @throws EmailAlreadyUsed when another person of the same account, for the same purpose, has the email
     */
    public function save(Person $person): void;
}
