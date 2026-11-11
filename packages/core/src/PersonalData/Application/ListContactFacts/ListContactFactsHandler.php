<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ListContactFacts;

use Prospecter\Core\PersonalData\Application\Port\ContactFactsRetriever;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFactsOutput;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFactsService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;

final readonly class ListContactFactsHandler implements QueryHandler, ListContactFactsService
{
    public function __construct(private ContactFactsRetriever $contactFactsRetriever)
    {
    }

    public function __invoke(ListContactFacts $query, ListContactFactsOutput $output): void
    {
        $slice = $this->contactFactsRetriever->sliceOf(new AccountId($query->accountId), $query->after, $query->max);

        $output->present($slice->content, $slice->next);
    }
}
