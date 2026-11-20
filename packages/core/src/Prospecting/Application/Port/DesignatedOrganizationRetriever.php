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
 * Reads which organization of an account a key designates, by what designates each one in its account, without
 * restoring them (ADR 0088).
 */
interface DesignatedOrganizationRetriever
{
    public function withSiret(AccountId $accountId, Siret $siret): ?OrganizationId;

    public function withReference(AccountId $accountId, ExternalReference $reference): ?OrganizationId;

    /**
     * The organization whose website has this domain: its host without `www.`.
     */
    public function withWebsiteDomain(AccountId $accountId, string $domain): ?OrganizationId;

    public function withEmail(AccountId $accountId, Email $email): ?OrganizationId;

    /**
     * @param list<OrganizationId> $ids
     *
     * @return list<Candidate> in the order of the ids, those that no longer exist absent
     */
    public function named(array $ids): array;
}
