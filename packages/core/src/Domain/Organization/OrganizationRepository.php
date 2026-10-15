<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Organization;

use Prospecter\Core\Domain\Shared\EmailAlreadyUsed;
use Prospecter\Core\Domain\Shared\Page;

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

    public function countProspectedBy(OrganizationId $ownerId): int;

    /**
     * Newest first; the query matches the corporate name or the email.
     *
     * @return Page<Organization>
     */
    public function pageProspectedBy(OrganizationId $ownerId, string $query, int $page, int $maxByPage): Page;
}
