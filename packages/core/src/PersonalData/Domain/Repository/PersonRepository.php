<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Repository;

use Prospecter\Core\PersonalData\Domain\Exception\LinkedInProfileTakenMeanwhile;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\LinkedInProfile;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
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

    public function remove(Person $person): void;

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
     * The other contact of the account holding this profile, as their own or as a former one, if any.
     */
    public function contactHoldingLinkedInProfile(AccountId $accountId, LinkedInProfile $profile, PersonId $except): ?Person;

    /**
     * @throws EmailAlreadyUsed              when another person took the email meanwhile (EmailUniqueness checked it before)
     * @throws LinkedInProfileTakenMeanwhile when another contact took a profile meanwhile (LinkedInProfileUniqueness checked it before)
     */
    public function save(Person $person): void;
}
