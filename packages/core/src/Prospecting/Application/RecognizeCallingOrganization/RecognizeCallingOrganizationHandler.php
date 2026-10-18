<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

final readonly class RecognizeCallingOrganizationHandler implements QueryHandler
{
    public function __construct(private OrganizationRepository $organizations, private ProspectorService $prospectorService)
    {
    }

    /**
     * @return list<OrganizationResponse> the organizations having the number
     */
    public function __invoke(RecognizeCallingOrganization $query): array
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($query->prospectorId));
        $organizations = $this->organizations->withPhoneNumber($prospector->accountId, PhoneNumber::of($query->phoneNumber));

        return array_map(static fn (Organization $organization): OrganizationResponse => OrganizationResponse::of($organization, $prospector), $organizations);
    }
}
