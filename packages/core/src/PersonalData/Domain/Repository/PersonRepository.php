<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Repository;

use Prospecter\Core\PersonalData\Domain\Exception\ReferenceTakenMeanwhile;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;

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
     * The account's other contacts who share this resemblance key.
     *
     * @return list<PersonId>
     */
    public function resembling(AccountId $accountId, PersonResemblanceKey $key, PersonId $except): array;

    /**
     * Whether another person in the account, kept for this purpose, has this email.
     */
    public function isEmailUsed(AccountId $accountId, Purpose $purpose, Email $email, PersonId $except): bool;

    /**
     * The account's contacts who have these emails.
     *
     * @param list<Email> $emails
     *
     * @return array<string, PersonId> the contact who has each email, keyed by the email; an email no contact has is absent
     */
    public function contactsHavingEmails(AccountId $accountId, array $emails): array;

    /**
     * The account's other contact who holds this link, as a current or a former link, if any.
     */
    public function contactHoldingReference(AccountId $accountId, ExternalReference $reference, PersonId $except): ?Person;

    /**
     * The account's contact who has this e-mail, if any.
     */
    public function contactWithEmail(AccountId $accountId, Email $email): ?Person;

    /**
     * @throws EmailAlreadyUsed        when another person took the email meanwhile (EmailUniqueness checked it before)
     * @throws ReferenceTakenMeanwhile when another contact took a link meanwhile (ReferenceUniqueness checked it before)
     */
    public function save(Person $person): void;
}
