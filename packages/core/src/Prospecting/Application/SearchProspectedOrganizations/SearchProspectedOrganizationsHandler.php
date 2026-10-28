<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\SearchKey;

final readonly class SearchProspectedOrganizationsHandler implements QueryHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(SearchProspectedOrganizations $query, SearchProspectedOrganizationsOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));

        $output->present(SearchProspectedOrganizationsResponse::of($this->organizations->sliceProspectedBy($prospector->accountId, SearchKey::of($query->query), $query->after, $query->max)));
    }
}
