<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\IsProspectedBy;

use Prospecter\Core\Prospecting\Contract\Service\IsProspectedBy\IsProspectedBy;
use Prospecter\Core\Prospecting\Contract\Service\IsProspectedBy\IsProspectedByOutput;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class IsProspectedByHandler implements QueryHandler
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    public function __invoke(IsProspectedBy $query, IsProspectedByOutput $output): void
    {
        $output->present($this->organizations->get(new OrganizationId($query->organizationId))->isProspectedBy(new AccountId($query->accountId)));
    }
}
