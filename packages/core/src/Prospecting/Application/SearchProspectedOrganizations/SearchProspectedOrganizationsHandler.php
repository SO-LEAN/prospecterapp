<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\Prospecting\Application\Port\FoundOrganizationRetriever;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKey;

final readonly class SearchProspectedOrganizationsHandler implements QueryHandler
{
    public function __construct(private FoundOrganizationRetriever $foundOrganizationRetriever, private Prospectors $prospectors)
    {
    }

    public function __invoke(SearchProspectedOrganizations $query, SearchProspectedOrganizationsOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));

        $output->present($this->foundOrganizationRetriever->sliceProspectedBy($prospector->accountId, SearchKey::of($query->query), $query->after, $query->max));
    }
}
