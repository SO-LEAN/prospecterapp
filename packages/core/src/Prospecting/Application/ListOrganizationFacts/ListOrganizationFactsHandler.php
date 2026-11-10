<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ListOrganizationFacts;

use Prospecter\Core\Prospecting\Application\Port\OrganizationFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\ListOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\ListOrganizationFactsOutput;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\ListOrganizationFactsService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;

final readonly class ListOrganizationFactsHandler implements QueryHandler, ListOrganizationFactsService
{
    public function __construct(private OrganizationFactsRetriever $organizationFactsRetriever)
    {
    }

    public function __invoke(ListOrganizationFacts $query, ListOrganizationFactsOutput $output): void
    {
        $slice = $this->organizationFactsRetriever->sliceOf(new AccountId($query->accountId), $query->after, $query->max);

        $output->present($slice->content, $slice->next);
    }
}
