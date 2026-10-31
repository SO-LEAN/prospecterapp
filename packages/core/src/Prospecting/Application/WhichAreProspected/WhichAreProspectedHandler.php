<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\WhichAreProspected;

use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspected;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspectedOutput;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspectedService;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class WhichAreProspectedHandler implements QueryHandler, WhichAreProspectedService
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    public function __invoke(WhichAreProspected $query, WhichAreProspectedOutput $output): void
    {
        $accountId = new AccountId($query->accountId);
        $prospected = array_filter($this->organizations->ofIds(array_map(static fn (string $id): OrganizationId => new OrganizationId($id), $query->organizationIds)), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId));

        $output->present(array_values(array_map(static fn (Organization $organization): string => $organization->id()->value, $prospected)));
    }
}
