<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Page;
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
     * The persons an account keeps for a purpose, newest first; the text matches the first name, the last
     * name or the email.
     *
     * @return Page<Person>
     */
    public function pageOf(AccountId $accountId, Purpose $purpose, string $text, int $page, int $maxByPage): Page;

    public function remove(Person $person): void;

    /**
     * Whether another person of the account, kept for this purpose, has this email.
     */
    public function isEmailUsed(AccountId $accountId, Purpose $purpose, Email $email, PersonId $except): bool;

    /**
     * @throws EmailAlreadyUsed when another person took the email meanwhile (EmailUniqueness checked it before)
     */
    public function save(Person $person): void;
}
