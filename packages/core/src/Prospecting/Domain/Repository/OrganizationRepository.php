<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Repository;

use Prospecter\Core\Prospecting\Domain\Exception\ReferenceAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\SiretAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Exception\WebsiteAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\ResemblanceKey;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
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
     * Whether another organization in the account's prospecting base has this email.
     */
    public function isEmailUsed(AccountId $accountId, Email $email, OrganizationId $except): bool;

    /**
     * The organizations in the account's prospecting base that have these emails.
     *
     * @param list<Email> $emails
     *
     * @return array<string, OrganizationId> the organization having each email, by the email; one none has absent
     */
    public function havingEmails(AccountId $accountId, array $emails): array;

    /**
     * The other organization in the account's prospecting base that has this SIRET, if any.
     */
    public function withSiret(AccountId $accountId, Siret $siret, OrganizationId $except): ?Organization;

    /**
     * The other organization in the account's prospecting base that has this link, if any.
     */
    public function withReference(AccountId $accountId, ExternalReference $reference, OrganizationId $except): ?Organization;

    /**
     * The other organization in the account's prospecting base whose website has this website's domain, if any.
     */
    public function withWebsiteOfDomain(AccountId $accountId, Website $website, OrganizationId $except): ?Organization;

    /**
     * The other organizations in the account's prospecting base that have this resemblance key.
     *
     * @return list<OrganizationId>
     */
    public function resembling(AccountId $accountId, ResemblanceKey $key, OrganizationId $except): array;

    /**
     * @throws EmailAlreadyUsed     when another organization took the email meanwhile (EmailUniqueness checked it before)
     * @throws SiretAlreadyUsed     when another organization took the SIRET meanwhile (SiretUniqueness checked it before)
     * @throws ReferenceAlreadyUsed when another organization took a link meanwhile (ReferenceUniqueness checked it before)
     * @throws WebsiteAlreadyUsed   when another organization took the domain meanwhile (WebsiteUniqueness checked it before)
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
     * The account's organizations that name any of these persons as a contact.
     *
     * @param list<PersonId> $contacts
     *
     * @return list<Organization>
     */
    public function withContacts(array $contacts, AccountId $accountId): array;
}
