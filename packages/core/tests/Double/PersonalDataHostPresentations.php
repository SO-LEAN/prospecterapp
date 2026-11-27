<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Domain\Model\HostPresentation;
use Prospecter\Core\Booking\Domain\Port\HostPresentations;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\KeptMemberPresentations;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembers;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembersService;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089, 0153, 0223). Booking learns from PersonalData how a host presents themselves.
 * It asks about a set of one.
 */
final readonly class PersonalDataHostPresentations implements HostPresentations
{
    public function __construct(private PresentMembersService $presentMembersService)
    {
    }

    public function presentationOf(string $personId): ?HostPresentation
    {
        $kept = new KeptMemberPresentations();
        ($this->presentMembersService)(new PresentMembers([$personId]), $kept);
        $presented = $kept->members[0] ?? null;

        return null === $presented ? null : new HostPresentation($presented->firstName, $presented->lastName, $presented->pictureUrl);
    }
}
