<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Repository;

use Prospecter\Core\Prospecting\Domain\Exception\LinkedInPageAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\SiretAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Exception\WebsiteAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\LinkedInPage;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\ResemblanceKey;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Siret;

interface OrganizationRepository
{
    /**
     * @throws UnknownOrganization
     */
    public function get(OrganizationId $id): Organization;

    /**
     * The organizations that exist among these ids, in no particular order.
     *
     * @param list<OrganizationId> $ids
     *
     * @return list<Organization>
     */
    public function ofIds(array $ids): array;

    /**
     * Whether another organization of the prospecting base of the account has this email.
     */
    public function isEmailUsed(AccountId $accountId, Email $email, OrganizationId $except): bool;

    /**
     * The organizations of the prospecting base of the account having these emails.
     *
     * @param list<Email> $emails
     *
     * @return array<string, OrganizationId> the organization having each email, by the email; one none has absent
     */
    public function havingEmails(AccountId $accountId, array $emails): array;

    /**
     * The other organization of the prospecting base of the account having this SIRET, if any.
     */
    public function withSiret(AccountId $accountId, Siret $siret, OrganizationId $except): ?Organization;

    /**
     * The other organization of the prospecting base of the account having this LinkedIn page, if any.
     */
    public function withLinkedInPage(AccountId $accountId, LinkedInPage $page, OrganizationId $except): ?Organization;

    /**
     * The other organization of the prospecting base of the account whose website has the domain of this one, if any.
     */
    public function withWebsiteOfDomain(AccountId $accountId, Website $website, OrganizationId $except): ?Organization;

    /**
     * The other organizations of the prospecting base of the account having this resemblance key.
     *
     * @return list<OrganizationId>
     */
    public function resembling(AccountId $accountId, ResemblanceKey $key, OrganizationId $except): array;

    /**
     * @throws EmailAlreadyUsed        when another organization took the email meanwhile (EmailUniqueness checked it before)
     * @throws SiretAlreadyUsed        when another organization took the SIRET meanwhile (SiretUniqueness checked it before)
     * @throws LinkedInPageAlreadyUsed when another organization took the LinkedIn page meanwhile (LinkedInPageUniqueness checked it before)
     * @throws WebsiteAlreadyUsed      when another organization took the domain meanwhile (WebsiteUniqueness checked it before)
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
}
