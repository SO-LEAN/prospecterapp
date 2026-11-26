<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\CandidateResponse;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Company\Siret;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;

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
     * @return list<CandidateResponse> in the ids' order, without the organizations that no longer exist
     */
    public function named(array $ids): array;
}
