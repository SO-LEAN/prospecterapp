<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganization;

use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class ConsultOrganizationHandler implements QueryHandler
{
    public function __construct(private OrganizationSheetRetriever $organizationSheetRetriever)
    {
    }

    public function __invoke(ConsultOrganization $query, ConsultOrganizationOutput $output): void
    {
        $output->present($this->organizationSheetRetriever->get(new OrganizationId($query->id))->offeringWhatItsStateAllows());
    }
}
