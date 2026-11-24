<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\ConsultOrganization;

use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

final readonly class ConsultOrganizationHandler implements QueryHandler
{
    public function __construct(private OrganizationSheetRetriever $organizationSheetRetriever)
    {
    }

    public function __invoke(ConsultOrganization $query, ConsultOrganizationOutput $output): void
    {
        $output->present($this->organizationSheetRetriever->get(new OrganizationId($query->id)));
    }
}
