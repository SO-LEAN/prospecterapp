<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Page;

interface OrganizationRepository
{
    public function nextIdentity(): OrganizationId;

    /**
     * @throws UnknownOrganization
     */
    public function get(OrganizationId $id): Organization;

    /**
     * @throws EmailAlreadyUsed
     */
    public function save(Organization $organization): void;

    /**
     * Newest first; the query matches the corporate name or the email.
     *
     * @return Page<Organization>
     */
    public function pageProspectedBy(AccountId $accountId, string $query, int $page, int $maxByPage): Page;
}
