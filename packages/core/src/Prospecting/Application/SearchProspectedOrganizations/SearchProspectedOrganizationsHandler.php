<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\SearchTerms;

final readonly class SearchProspectedOrganizationsHandler implements QueryHandler
{
    public function __construct(private OrganizationRepository $organizations, private ProspectorService $prospectorService)
    {
    }

    public function __invoke(SearchProspectedOrganizations $query): SearchProspectedOrganizationsResponse
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($query->prospectorId));

        return SearchProspectedOrganizationsResponse::of($this->organizations->sliceProspectedBy($prospector->accountId, SearchTerms::of($query->query), $query->after, $query->max));
    }
}
