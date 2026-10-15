<?php

declare(strict_types=1);

namespace Prospecter\Core\Service;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Exception\Gateway;
use Prospecter\Core\Exception\UseCase\NotFoundException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;

/**
 * The organizations a user's organization may reach. Any other one is reported as not found, so that
 * nobody learns that it exists.
 */
final readonly class ProspectedOrganizations
{
    public function __construct(private OrganizationGateway $organizations)
    {
    }

    /**
     * My own organization or one it prospects.
     */
    public function visibleTo(int $organizationId, int $id): Organization
    {
        $organization = $this->get($id, 'id');

        return $organization->getId() === $organizationId || $this->isProspectedBy($organizationId, $organization)
            ? $organization
            : throw $this->notFound($id, 'id');
    }

    public function prospectedBy(int $organizationId, int $id): Organization
    {
        return $this->prospected($organizationId, $id, 'id');
    }

    public function holdingProspectedBy(int $organizationId, ?int $id): ?Organization
    {
        return null === $id ? null : $this->prospected($organizationId, $id, 'holdBy');
    }

    private function prospected(int $organizationId, int $id, string $field): Organization
    {
        $organization = $this->get($id, $field);

        return $this->isProspectedBy($organizationId, $organization) ? $organization : throw $this->notFound($id, $field);
    }

    private function isProspectedBy(int $organizationId, Organization $organization): bool
    {
        return $organization->getOwnedBy()?->getId() === $organizationId;
    }

    private function get(int $id, string $field): Organization
    {
        try {
            return $this->organizations->get($id);
        } catch (Gateway\NotFoundException $e) {
            throw $this->notFound($id, $field, $e);
        }
    }

    private function notFound(int $id, string $field, ?Gateway\NotFoundException $previous = null): NotFoundException
    {
        return new NotFoundException(sprintf('Organization with #ID %d not found', $id), [$field => 'Organization not found'], $previous);
    }
}
