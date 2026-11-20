<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The organizations of the prospecting base of an account an organization resembles, by its name and its city:
 * a rule on the whole base, out of reach of one organization alone.
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
