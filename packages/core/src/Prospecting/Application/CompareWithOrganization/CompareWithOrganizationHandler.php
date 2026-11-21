<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CompareWithOrganization;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationSupplement;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\Comparison;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Asks the organization itself, restored by its repository, the rule its completion follows: the answer and the
 * write never differ (ADR 0177).
 */
final readonly class CompareWithOrganizationHandler implements QueryHandler
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    public function __invoke(CompareWithOrganization $query, CompareWithOrganizationOutput $output): void
    {
        $completion = $this->organizations->get(new OrganizationId($query->id))->completionWith(OrganizationSupplement::given($query->siret, $query->links, $query->website, $query->email, $query->phoneNumber));
        $output->present(new Comparison($completion->completed, $completion->differing));
    }
}
