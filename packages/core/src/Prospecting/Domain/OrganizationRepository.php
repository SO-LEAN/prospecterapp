<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Page;
use Prospecter\Core\SharedKernel\Domain\PersonId;

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
     * Newest first; the query matches the corporate name or the email.
     *
     * @return Page<Organization>
     */
    public function pageProspectedBy(AccountId $accountId, string $query, int $page, int $maxByPage): Page;
}
