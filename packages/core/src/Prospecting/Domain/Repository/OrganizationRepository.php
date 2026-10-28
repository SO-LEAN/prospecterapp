<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Repository;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\SearchKey;
use Prospecter\Core\SharedKernel\Domain\Slice;

interface OrganizationRepository
{
    /**
     * @throws UnknownOrganization
     */
    public function get(OrganizationId $id): Organization;

    /**
     * Whether another organization of the prospecting base of the account has this email.
     */
    public function isEmailUsed(AccountId $accountId, Email $email, OrganizationId $except): bool;

    /**
     * @throws EmailAlreadyUsed when another organization took the email meanwhile (EmailUniqueness checked it before)
     */
    public function save(Organization $organization): void;

    public function remove(Organization $organization): void;

    /**
     * The organizations attached to this one as their holding.
     *
     * @return list<Organization>
     */
    public function subsidiariesOf(OrganizationId $holding): array;

    /**
     * The organizations of the account naming any of these persons as a contact.
     *
     * @param list<PersonId> $contacts
     *
     * @return list<Organization>
     */
    public function withContacts(array $contacts, AccountId $accountId): array;

    /**
     * The organizations the account prospects that have this phone number.
     *
     * @return list<Organization>
     */
    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array;

    /**
     * The organizations the account prospects, newest first, after the cursor: those found by the search
     * (`Organization::searchKeys()`), all of them without one.
     *
     * @return Slice<Organization>
     */
    public function sliceProspectedBy(AccountId $accountId, ?SearchKey $search, ?string $after, int $max): Slice;
}
