<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Service;

use Prospecter\Core\Application\Exception\NotFoundException;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\Organization\UnknownOrganization;

/**
 * The organizations a user's organization may reach. Any other one is reported as not found, so that
 * nobody learns that it exists (ADR 0019).
 */
final readonly class ProspectedOrganizations
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    /**
     * My own organization or one it prospects.
     */
    public function visibleTo(OrganizationId $organizationId, OrganizationId $id): Organization
    {
        $organization = $this->get($id, 'id');

        return $organization->isVisibleTo($organizationId) ? $organization : throw $this->notFound($id, 'id');
    }

    public function prospectedBy(OrganizationId $organizationId, OrganizationId $id): Organization
    {
        return $this->prospected($organizationId, $id, 'id');
    }

    public function holdingProspectedBy(OrganizationId $organizationId, OrganizationId $id): Organization
    {
        return $this->prospected($organizationId, $id, 'holdBy');
    }

    private function prospected(OrganizationId $organizationId, OrganizationId $id, string $field): Organization
    {
        $organization = $this->get($id, $field);

        return $organization->isProspectedBy($organizationId) ? $organization : throw $this->notFound($id, $field);
    }

    private function get(OrganizationId $id, string $field): Organization
    {
        try {
            return $this->organizations->get($id);
        } catch (UnknownOrganization $e) {
            throw $this->notFound($id, $field, $e);
        }
    }

    private function notFound(OrganizationId $id, string $field, ?UnknownOrganization $previous = null): NotFoundException
    {
        return new NotFoundException(sprintf('Organization %s not found', $id), [$field => 'Organization not found'], $previous);
    }
}
