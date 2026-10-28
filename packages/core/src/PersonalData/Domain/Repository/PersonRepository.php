<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Repository;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\SearchKey;
use Prospecter\Core\SharedKernel\Domain\Slice;

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
     * The persons an account keeps for a purpose, newest first, after the cursor: those found by the search
     * (`Person::searchKeys()`), all of them without one.
     *
     * @return Slice<Person>
     */
    public function sliceOf(AccountId $accountId, Purpose $purpose, ?SearchKey $search, ?string $after, int $max): Slice;

    /**
     * The persons an account keeps for a purpose who have this phone number.
     *
     * @return list<Person>
     */
    public function withPhoneNumber(AccountId $accountId, Purpose $purpose, PhoneNumber $phoneNumber): array;

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
