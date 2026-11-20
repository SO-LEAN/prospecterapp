<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Repository;

use Prospecter\Core\PersonalData\Domain\Exception\ReferenceTakenMeanwhile;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

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

    public function remove(Person $person): void;

    /**
     * The other contacts of the account sharing this resemblance key.
     *
     * @return list<PersonId>
     */
    public function resembling(AccountId $accountId, PersonResemblanceKey $key, PersonId $except): array;

    /**
     * Whether another person of the account, kept for this purpose, has this email.
     */
    public function isEmailUsed(AccountId $accountId, Purpose $purpose, Email $email, PersonId $except): bool;

    /**
     * The contacts of the account having these emails.
     *
     * @param list<Email> $emails
     *
     * @return array<string, PersonId> the contact having each email, by the email; one none has absent
     */
    public function contactsHavingEmails(AccountId $accountId, array $emails): array;

    /**
     * The other contact of the account holding this link, as one of their own or as a former one, if any.
     */
    public function contactHoldingReference(AccountId $accountId, ExternalReference $reference, PersonId $except): ?Person;

    /**
     * The contact of the account having this e-mail, if any.
     */
    public function contactWithEmail(AccountId $accountId, Email $email): ?Person;

    /**
     * A contact of the account having this phone number, if any.
     */
    public function contactWithPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): ?Person;

    /**
     * @throws EmailAlreadyUsed        when another person took the email meanwhile (EmailUniqueness checked it before)
     * @throws ReferenceTakenMeanwhile when another contact took a link meanwhile (ReferenceUniqueness checked it before)
     */
    public function save(Person $person): void;
}
