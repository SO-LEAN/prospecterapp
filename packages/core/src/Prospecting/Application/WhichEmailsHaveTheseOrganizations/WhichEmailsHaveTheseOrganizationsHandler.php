<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\WhichEmailsHaveTheseOrganizations;

use Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations\WhichEmailsHaveTheseOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations\WhichEmailsHaveTheseOrganizationsOutput;
use Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations\WhichEmailsHaveTheseOrganizationsService;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

final readonly class WhichEmailsHaveTheseOrganizationsHandler implements QueryHandler, WhichEmailsHaveTheseOrganizationsService
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    public function __invoke(WhichEmailsHaveTheseOrganizations $query, WhichEmailsHaveTheseOrganizationsOutput $output): void
    {
        $accountId = new AccountId($query->accountId);
        $organizations = $this->organizations->ofIds(array_map(static fn (string $id): OrganizationId => new OrganizationId($id), $query->organizationIds));
        $prospected = array_filter($organizations, static fn (Organization $organization): bool => $organization->isProspectedBy($accountId));

        $output->present(array_column(array_map(static fn (Organization $organization): array => [$organization->id()->value, $organization->contactDetails()->email?->value], $prospected), 1, 0));
    }
}
