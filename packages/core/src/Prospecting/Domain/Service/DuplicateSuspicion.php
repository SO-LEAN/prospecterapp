<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;

/**
 * The organizations in an account's prospecting base that an organization resembles by its name and its city. The rule
 * covers the whole base, out of one organization's reach.
 */
final readonly class DuplicateSuspicion
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    /**
     * @return list<OrganizationId>
     */
    #[\NoDiscard]
    public function resemblingTo(Organization $organization): array
    {
        $key = $organization->resemblanceKey();

        return null === $key ? [] : $this->organizations->resembling($organization->accountId(), $key, except: $organization->id());
    }
}
