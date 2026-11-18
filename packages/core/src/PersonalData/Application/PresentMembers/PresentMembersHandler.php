<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\PresentMembers;

use Prospecter\Core\PersonalData\Application\Port\MemberPresentationRetriever;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\MemberPresentationResponse;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembers;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembersOutput;
use Prospecter\Core\PersonalData\Contract\Service\PresentMembers\PresentMembersService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class PresentMembersHandler implements QueryHandler, PresentMembersService
{
    public function __construct(private MemberPresentationRetriever $memberPresentationRetriever)
    {
    }

    public function __invoke(PresentMembers $query, PresentMembersOutput $output): void
    {
        $presentations = $this->memberPresentationRetriever->ofIds(array_map(static fn (string $id): PersonId => new PersonId($id), $query->personIds));

        $output->present(array_map(static fn (MemberPresentation $member): MemberPresentationResponse => new MemberPresentationResponse($member->personId, $member->firstName, $member->lastName, $member->picture?->url), $presentations));
    }
}
