<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Candidate;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Siret;

/**
 * Reads which of an account's organizations a key designates, without restoring them (ADR 0088). It looks the key up
 * among what designates each organization in its account.
 */
interface DesignatedOrganizationRetriever
{
    public function withSiret(AccountId $accountId, Siret $siret): ?OrganizationId;

    public function withReference(AccountId $accountId, ExternalReference $reference): ?OrganizationId;

    /**
     * Returns the organization whose website has this domain. The domain is the website's host without `www.`.
     */
    public function withWebsiteDomain(AccountId $accountId, string $domain): ?OrganizationId;

    public function withEmail(AccountId $accountId, Email $email): ?OrganizationId;

    /**
     * @param list<OrganizationId> $ids
     *
     * @return list<Candidate> in the ids' order, without the organizations that no longer exist
     */
    public function named(array $ids): array;
}
