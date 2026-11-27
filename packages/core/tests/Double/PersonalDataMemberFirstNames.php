<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Port\MemberFirstNames;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\KeptMemberPresentations;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembers;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembersService;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089, 0223). From PersonalData, Connectors learns the first name a member
 * presents themselves with (ADR 0175). It asks about a set of one.
 */
final readonly class PersonalDataMemberFirstNames implements MemberFirstNames
{
    public function __construct(private PresentMembersService $presentMembersService)
    {
    }

    public function firstNameOf(string $personId): ?string
    {
        $kept = new KeptMemberPresentations();
        ($this->presentMembersService)(new PresentMembers([$personId]), $kept);

        return $kept->members[0]->firstName ?? null;
    }
}
